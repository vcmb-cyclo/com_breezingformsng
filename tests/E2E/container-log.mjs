import { spawnSync } from 'node:child_process';

const WARNING = /PHP (Warning|Notice|Deprecated)/;

/**
 * The error output of a container, as one string: Apache's error log, which
 * holds the PHP warnings. A failing `docker logs` is an error, not an empty log.
 */
export function containerErrors(container) {
    const run = spawnSync('docker', ['logs', container], { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });

    if (run.error || run.status !== 0) {
        throw new Error(`docker logs ${container} failed (${run.error?.message ?? `exit ${run.status}`}): ${run.stderr}`);
    }

    return run.stderr;
}

/**
 * The PHP warnings, notices and deprecations a log gained since an earlier read.
 * The log only grows: the characters added are cut first, then split into lines,
 * so the first new line is never lost to the empty entry that follows a final
 * line break.
 */
export function addedWarnings(before, after) {
    if (!after.startsWith(before)) {
        throw new Error('The log does not continue the one read before: it was cut or replaced.');
    }

    return after.slice(before.length).split('\n').filter((line) => WARNING.test(line));
}
