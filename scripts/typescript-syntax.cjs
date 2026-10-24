const fs = require('node:fs');
const path = require('node:path');
let ts;
try { ts = require('typescript'); }
catch {
  try { ts = require('/opt/nvm/versions/node/v22.16.0/lib/node_modules/typescript'); }
  catch { console.error('TypeScript compiler unavailable. Run npm install first.'); process.exit(2); }
}
const root = path.join(__dirname, '../frontend/src');
let files = 0;
let failures = 0;
function scan(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const p = path.join(directory, entry.name);
    if (entry.isDirectory()) { scan(p); continue; }
    if (!p.endsWith('.tsx') && !p.endsWith('.ts')) continue;
    files++;
    const result = ts.transpileModule(fs.readFileSync(p, 'utf8'), {
      fileName: p,
      reportDiagnostics: true,
      compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ESNext, jsx: ts.JsxEmit.Preserve },
    });
    for (const d of result.diagnostics || []) {
      if (d.category === ts.DiagnosticCategory.Error) {
        console.error(`${p}: ${ts.flattenDiagnosticMessageText(d.messageText, '\n')}`);
        failures++;
      }
    }
  }
}
scan(root);
if (failures) { console.error(`FAIL: ${failures} TypeScript parser diagnostics`); process.exit(1); }
console.log(`PASS: ${files} TS/TSX syntax checks (NOT a typecheck, build or runtime test)`);
