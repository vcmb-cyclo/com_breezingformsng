/**
 * Data model for the QuickMode tree.
 *
 * This module deliberately knows nothing about the DOM, jQuery or jTree. The
 * JSON tree remains the single source of truth while the view is migrated.
 */

const NODE_CLASSES = Object.freeze({
    root: 'bfQuickModeRootClass',
    page: 'bfQuickModePageClass',
    section: 'bfQuickModeSectionClass',
    element: 'bfQuickModeElementClass'
});

export class QuickmodeTreeModel {
    constructor(root) {
        this.root = root;
        this.nextIdentifier = 0;
        this.nodes = new Map();
        this.parents = new Map();
        this.rebuildIndex();
    }

    rebuildIndex() {
        this.nodes.clear();
        this.parents.clear();

        if (this.root) {
            this.indexNode(this.root, null);
        }

        return this;
    }

    indexNode(node, parent) {
        const id = node && node.attributes && node.attributes.id;

        if (!id) {
            throw new Error('QuickMode tree nodes require an identifier.');
        }

        if (this.nodes.has(id)) {
            throw new Error('QuickMode tree node identifiers must be unique.');
        }

        this.nodes.set(id, node);
        this.parents.set(id, parent);

        if (Array.isArray(node.children)) {
            node.children.forEach((child) => this.indexNode(child, node));
        }
    }

    find(id, start = this.root) {
        if (!id || !start) {
            return null;
        }

        if (start === this.root) {
            return this.nodes.get(id) || null;
        }

        if (start.attributes && start.attributes.id === id) {
            return start;
        }

        if (Array.isArray(start.children)) {
            for (const child of start.children) {
                const result = this.find(id, child);

                if (result) {
                    return result;
                }
            }
        }

        return null;
    }

    getParent(id) {
        return this.parents.get(id) || null;
    }

    getNodeClass(node) {
        return node && node.attributes ? (node.attributes.class || '') : '';
    }

    getNodeType(node) {
        if (!node) {
            return '';
        }

        if (node.properties && node.properties.type) {
            return node.properties.type;
        }

        return Object.keys(NODE_CLASSES).find((type) => NODE_CLASSES[type] === this.getNodeClass(node)) || '';
    }

    getChildren(node) {
        return node && Array.isArray(node.children) ? node.children : [];
    }

    getElements(start = this.root, result = []) {
        if (!start) {
            return result;
        }

        if (this.getNodeType(start) === 'element') {
            result.push(start);
        }

        this.getChildren(start).forEach((child) => this.getElements(child, result));

        return result;
    }

    replace(id, replacement) {
        const current = this.find(id);
        const parent = this.getParent(id);

        if (!current || !parent || !replacement || !replacement.attributes || !replacement.attributes.id) {
            return false;
        }

        if (replacement.attributes.id !== id && this.nodes.has(replacement.attributes.id)) {
            throw new Error('QuickMode tree node identifiers must be unique.');
        }

        const index = parent.children.indexOf(current);
        parent.children[index] = replacement;
        this.rebuildIndex();

        return true;
    }

    canContain(target, source) {
        const targetType = this.getNodeType(target);
        const sourceType = this.getNodeType(source);

        if (targetType === 'root') {
            return sourceType === 'page';
        }

        if (targetType === 'page' || targetType === 'section') {
            return sourceType === 'section' || sourceType === 'element';
        }

        return false;
    }

    isDescendant(ancestor, candidate) {
        let current = this.getParent(candidate && candidate.attributes && candidate.attributes.id);

        while (current) {
            if (current === ancestor) {
                return true;
            }

            current = this.getParent(current.attributes.id);
        }

        return false;
    }

    remove(id) {
        const node = this.find(id);
        const parent = this.getParent(id);

        if (!node) {
            return null;
        }

        if (!parent) {
            throw new Error('The QuickMode tree root cannot be removed.');
        }

        const index = parent.children.indexOf(node);
        parent.children.splice(index, 1);
        this.rebuildIndex();

        return { node, parent, index };
    }

    insert(targetId, node, index = null) {
        const target = this.find(targetId);

        if (!target || !node || !node.attributes || !node.attributes.id) {
            return false;
        }

        if (!this.canContain(target, node)) {
            throw new Error('This QuickMode tree node cannot contain the new node.');
        }

        if (this.nodes.has(node.attributes.id)) {
            throw new Error('QuickMode tree node identifiers must be unique.');
        }

        const children = this.ensureChildren(target);
        const insertionIndex = index === null ? children.length : this.normalizeIndex(index, children.length);
        children.splice(insertionIndex, 0, node);
        this.rebuildIndex();

        return { node, parent: target, index: insertionIndex };
    }

    move(sourceId, targetId, position = 'inside', index = null) {
        const source = this.find(sourceId);
        const target = this.find(targetId);

        if (!source || !target || source === this.root || source === target) {
            throw new Error('Invalid QuickMode tree move.');
        }

        if (this.isDescendant(source, target)) {
            throw new Error('A QuickMode tree node cannot be moved into its descendant.');
        }

        let destination;
        let children;
        let insertionIndex;

        if (position === 'inside') {
            if (!this.canContain(target, source)) {
                throw new Error('This QuickMode tree node cannot contain the moved node.');
            }

            destination = target;
            children = this.ensureChildren(target);
            insertionIndex = index === null ? children.length : this.normalizeIndex(index, children.length);
        } else if (position === 'before' || position === 'after') {
            destination = this.getParent(targetId);

            if (!destination || !this.canContain(destination, source)) {
                throw new Error('The moved node has no valid sibling destination.');
            }

            children = destination.children;
            insertionIndex = children.indexOf(target) + (position === 'after' ? 1 : 0);
        } else {
            throw new Error('Unknown QuickMode tree move position.');
        }

        const sourceParent = this.getParent(sourceId);
        const sourceIndex = sourceParent.children.indexOf(source);

        if (position !== 'inside' && sourceParent === destination && sourceIndex < insertionIndex) {
            insertionIndex--;
        }

        sourceParent.children.splice(sourceIndex, 1);
        children.splice(insertionIndex, 0, source);
        this.rebuildIndex();

        return { node: source, parent: destination, index: insertionIndex };
    }

    cloneInto(sourceId, targetId, index = null) {
        const source = this.find(sourceId);
        const target = this.find(targetId);

        if (!source || !target || !['section', 'element'].includes(this.getNodeType(source))) {
            throw new Error('Only sections and elements can be copied.');
        }

        if (!['page', 'section'].includes(this.getNodeType(target))) {
            throw new Error('A copy can only be pasted into a page or section.');
        }

        const clone = JSON.parse(JSON.stringify(source));
        this.recreateIds(clone);

        return this.insert(targetId, clone, index);
    }

    recreateIds(node) {
        if (!node) {
            return node;
        }

        const type = this.getNodeType(node);
        const prefix = type === 'section' ? 'bfQuickModeSection' : 'bfQuickMode';
        const id = this.createUniqueIdentifier(prefix);

        node.attributes.id = id;

        if (!node.properties) {
            node.properties = {};
        }

        if (type === 'element') {
            node.properties.bfName = id;
            node.properties.dbId = 0;
        }

        node.properties.name = id;

        this.getChildren(node).forEach((child) => this.recreateIds(child));

        return node;
    }

    renumberPages(pageLabel = 'Page') {
        const root = this.root;

        if (!root || this.getNodeType(root) !== 'root') {
            return 0;
        }

        const pages = this.getChildren(root);
        let pageNumber = 0;

        pages.forEach((page) => {
            if (this.getNodeType(page) !== 'page') {
                return;
            }

            pageNumber++;
            page.attributes.id = `bfQuickModePage${pageNumber}`;
            page.data = page.data || {};
            page.data.title = `${pageLabel}${pageNumber}`;
            page.properties = page.properties || {};
            page.properties.pageNumber = pageNumber;
        });

        this.updateThankYouPage(pageNumber);
        this.rebuildIndex();

        return pageNumber;
    }

    updateThankYouPage(pageCount) {
        const root = this.root;

        if (!root || !root.properties) {
            return;
        }

        if (root.properties.lastPageThankYou && pageCount > 1) {
            root.properties.submittedScriptCondidtion = 2;
            root.properties.submittedScriptCode = 'function ff_' + root.properties.name
                + '_submitted(status, message){if(status==0){ff_switchpage(' + pageCount
                + ');}else{alert(message);}}';
        } else {
            root.properties.submittedScriptCondidtion = -1;
        }
    }

    ensureChildren(node) {
        if (!Array.isArray(node.children)) {
            node.children = [];
        }

        return node.children;
    }

    normalizeIndex(index, length) {
        const numericIndex = Number(index);

        return Number.isFinite(numericIndex)
            ? Math.max(0, Math.min(numericIndex, length))
            : length;
    }

    createUniqueIdentifier(prefix) {
        let candidate;

        do {
            this.nextIdentifier++;
            candidate = `${prefix}${this.nextIdentifier}`;
        } while (this.nodes.has(candidate));

        return candidate;
    }
}

export default QuickmodeTreeModel;
