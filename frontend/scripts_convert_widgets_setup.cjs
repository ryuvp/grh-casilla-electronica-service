const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function findMatchingBrace(str, openIndex) {
  let depth = 0;
  let inSingle = false;
  let inDouble = false;
  let inTemplate = false;
  let inLineComment = false;
  let inBlockComment = false;

  for (let i = openIndex; i < str.length; i++) {
    const ch = str[i];
    const prev = i > 0 ? str[i - 1] : '';
    const next = i + 1 < str.length ? str[i + 1] : '';

    if (inLineComment) {
      if (ch === '\n') inLineComment = false;
      continue;
    }
    if (inBlockComment) {
      if (prev === '*' && ch === '/') inBlockComment = false;
      continue;
    }
    if (!inSingle && !inDouble && !inTemplate) {
      if (ch === '/' && next === '/') {
        inLineComment = true;
        i++;
        continue;
      }
      if (ch === '/' && next === '*') {
        inBlockComment = true;
        i++;
        continue;
      }
    }

    if (!inDouble && !inTemplate && ch === "'" && prev !== '\\') {
      inSingle = !inSingle;
      continue;
    }
    if (!inSingle && !inTemplate && ch === '"' && prev !== '\\') {
      inDouble = !inDouble;
      continue;
    }
    if (!inSingle && !inDouble && ch === '`' && prev !== '\\') {
      inTemplate = !inTemplate;
      continue;
    }

    if (inSingle || inDouble || inTemplate) continue;

    if (ch === '{') depth++;
    if (ch === '}') {
      depth--;
      if (depth === 0) return i;
    }
  }
  return -1;
}

function normalizeVueImport(script) {
  return script.replace(/import\s*\{([^}]*)\}\s*from\s*["']vue["'];?/g, (_m, names) => {
    const kept = names
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .filter((n) => !/^defineComponent$/.test(n) && !/^type\s+/.test(n));

    if (!kept.length) return '';
    return `import { ${kept.join(', ')} } from "vue";`;
  });
}

function convertFile(filePath) {
  const src = fs.readFileSync(filePath, 'utf8');
  const scriptMatch = src.match(/<script\s+lang="ts">([\s\S]*?)<\/script>/);
  if (!scriptMatch) return { status: 'skip-no-ts' };

  const scriptFull = scriptMatch[0];
  let script = scriptMatch[1];

  if (script.includes('<script setup>')) {
    return { status: 'skip-corrupt' };
  }

  script = script.replace(/^\s+|\s+$/g, '');
  script = script.replace(/^import\s+type\s+.*?;\s*$/gm, '');
  script = normalizeVueImport(script);

  const expIdx = script.indexOf('export default defineComponent(');
  if (expIdx === -1) return { status: 'skip-no-definecomponent' };

  const objStart = script.indexOf('{', expIdx);
  if (objStart === -1) return { status: 'skip-no-obj-start' };
  const objEnd = findMatchingBrace(script, objStart);
  if (objEnd === -1) return { status: 'skip-no-obj-end' };

  const afterObj = script.indexOf(');', objEnd);
  if (afterObj === -1) return { status: 'skip-no-component-end' };

  const optionsBody = script.slice(objStart + 1, objEnd);
  const tail = script.slice(afterObj + 2).trim();

  const nameMatch = optionsBody.match(/name\s*:\s*"([^"]+)"/);
  const compName = nameMatch ? nameMatch[1] : path.basename(filePath, '.vue');

  let propsCode = '';
  const propsIdx = optionsBody.indexOf('props:');
  if (propsIdx !== -1) {
    const propsObjStart = optionsBody.indexOf('{', propsIdx);
    if (propsObjStart !== -1) {
      const propsObjEnd = findMatchingBrace(optionsBody, propsObjStart);
      if (propsObjEnd !== -1) {
        propsCode = optionsBody.slice(propsObjStart, propsObjEnd + 1).trim();
      }
    }
  }

  const setupSig = optionsBody.match(/setup\s*\(([^)]*)\)\s*\{/);
  if (!setupSig) return { status: 'skip-no-setup' };

  const setupSigIdx = optionsBody.indexOf(setupSig[0]);
  const setupBodyStart = optionsBody.indexOf('{', setupSigIdx);
  const setupBodyEnd = findMatchingBrace(optionsBody, setupBodyStart);
  if (setupBodyEnd === -1) return { status: 'skip-setup-end' };

  let setupBody = optionsBody.slice(setupBodyStart + 1, setupBodyEnd).trim();
  setupBody = setupBody.replace(/\n\s*return\s*\{[\s\S]*?\};?\s*$/m, '\n');
  setupBody = setupBody.trim();

  const setupArgs = (setupSig[1] || '').trim();
  const needsPropsVar = /(^|\W)props(\W|$)/.test(setupArgs);

  const importsPart = script.slice(0, expIdx).trim().replace(/\n{3,}/g, '\n\n');

  const out = [];
  out.push('<script setup>');
  if (importsPart) out.push(importsPart);
  out.push('');
  out.push('defineOptions({');
  out.push(`  name: "${compName}",`);
  out.push('});');
  out.push('');

  if (propsCode) {
    if (needsPropsVar) {
      out.push(`const props = defineProps(${propsCode});`);
    } else {
      out.push(`defineProps(${propsCode});`);
    }
    out.push('');
  }

  if (setupBody) {
    out.push(setupBody);
    out.push('');
  }

  if (tail) {
    out.push(tail);
    out.push('');
  }

  out.push('</script>');

  const newScript = out.join('\n').replace(/\n{3,}/g, '\n\n');
  const next = src.replace(scriptFull, newScript);
  fs.writeFileSync(filePath, next);
  return { status: 'converted' };
}

const filesRaw = execSync(
  "find src/components/widgets -type f -name '*.vue' | sort",
  { encoding: 'utf8' }
).trim();

const files = (filesRaw ? filesRaw.split('\n') : []).filter((rel) => {
  const full = path.resolve(rel);
  const content = fs.readFileSync(full, 'utf8');
  return /<script\s+lang=\"ts\">/.test(content);
});
const report = { converted: [], skipped: [] };

for (const rel of files) {
  const full = path.resolve(rel);
  const result = convertFile(full);
  if (result.status === 'converted') report.converted.push(rel);
  else report.skipped.push(`${rel} :: ${result.status}`);
}

console.log(`converted=${report.converted.length}`);
if (report.converted.length) console.log(report.converted.join('\n'));
console.log(`skipped=${report.skipped.length}`);
if (report.skipped.length) console.log(report.skipped.join('\n'));
