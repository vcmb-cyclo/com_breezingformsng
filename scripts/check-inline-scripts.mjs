#!/usr/bin/env node
// Syntax check of the JavaScript written inline in the PHP templates and
// layouts, which ESLint does not see: each <script> block is parsed once its
// PHP tags are replaced by a neutral value.
//
// Usage: node scripts/check-inline-scripts.mjs
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { Linter } from 'eslint';

const files = execFileSync('git', ['ls-files', '*.php'], { encoding: 'utf8' })
    .split('\n')
    .filter((file) => /\/(tmpl|layouts)\//.test(file) && !/\/(vendor|libraries)\//.test(file));
// The end tag may carry spaces or attributes (</script >), as browsers accept.
const scriptPattern = /<script\b([^>]*)>([\s\S]*?)<\/script\b[^>]*>/gi;
const linter = new Linter();
let errors = 0;

for (const file of files) {
    const source = readFileSync(file, 'utf8');

    for (const match of source.matchAll(scriptPattern)) {
        const [, attributes, body] = match;
        const type = /\btype=["']?([^"'\s>]+)/i.exec(attributes)?.[1] ?? 'text/javascript';

        if (/\bsrc=/i.test(attributes) || !/^(text\/javascript|module|application\/javascript)$/i.test(type) || body.trim() === '') {
            continue;
        }

        // Keep the line numbers: a PHP tag becomes 0 plus its own line breaks.
        const code = body.replace(/<\?(?:php|=)[\s\S]*?\?>/g, (tag) => '0' + '\n'.repeat((tag.match(/\n/g) ?? []).length));
        const firstLine = source.slice(0, match.index).split('\n').length;
        const messages = linter.verify(code, {
            languageOptions: { ecmaVersion: 'latest', sourceType: type === 'module' ? 'module' : 'script' },
        });

        for (const message of messages.filter((item) => item.fatal)) {
            errors++;
            console.error(`${file}:${firstLine + message.line - 1}: ${message.message}`);
        }
    }
}

if (errors > 0) {
    console.error(`${errors} inline script(s) with a syntax error.`);
    process.exit(1);
}

console.log('Inline scripts OK.');
