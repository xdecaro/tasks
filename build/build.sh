#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
COMPONENT="$ROOT/component"
TASK_PLUGIN="$ROOT/plugins/task/xdecarotasks"
PACKAGE="$ROOT/package/pkg_xdecarotasks"
DIST="$ROOT/dist"
command -v php >/dev/null 2>&1 || { echo "PHP CLI required" >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 required" >&2; exit 1; }
for dir in "$COMPONENT" "$TASK_PLUGIN" "$PACKAGE" "$ROOT/build"; do while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find "$dir" -type f -name '*.php' -print0); done
if grep -R -nE --include='*.php' -- '->bind\([^,]+,[[:space:]]*\$[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=' "$COMPONENT" "$TASK_PLUGIN" "$PACKAGE"; then echo "Inline assignment passed to bind()." >&2; exit 1; fi
ROOT="$ROOT" VERSION="$VERSION" php -r '
$root=getenv("ROOT"); $version=getenv("VERSION");
$files=[$root."/component/xdecarotasks.xml",$root."/plugins/task/xdecarotasks/xdecarotasks.xml",$root."/plugins/task/xdecarotasks/forms/due.xml",$root."/package/pkg_xdecarotasks/pkg_xdecarotasks.xml",$root."/updates/pkg_xdecarotasks.xml",$root."/updates/changelog.xml"];
foreach($files as $file){if(!is_file($file)||simplexml_load_file($file)===false){fwrite(STDERR,"Invalid XML: {$file}\n");exit(1);}}
foreach([$files[0],$files[1],$files[3]] as $file){$xml=simplexml_load_file($file);if(trim((string)$xml->version)!==$version){fwrite(STDERR,"VERSION mismatch: {$file}\n");exit(1);}}
$manifest=simplexml_load_file($files[0]); $admin=trim((string)$manifest->administration->files["folder"]); $installed=$root."/component/".$admin;
foreach($manifest->install->sql->file as $sql){if(strtolower(trim((string)$sql["charset"]))!=="utf8"||!is_file($installed."/".trim((string)$sql))){fwrite(STDERR,"Invalid install SQL mapping.\n");exit(1);}}
$feed=simplexml_load_file($files[4]);if(trim((string)$feed->update->version)!==$version){fwrite(STDERR,"Feed version mismatch.\n");exit(1);}
'
for table in items assignees checklist comments history; do grep -q "#__xdecarotasks_${table}" "$COMPONENT/admin/sql/install.mysql.utf8mb4.sql" || { echo "Missing table ${table}" >&2; exit 1; }; done
rm -rf "$DIST"; mkdir -p "$DIST"
ROOT="$ROOT" VERSION="$VERSION" python3 - <<'PY'
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib, os
root=Path(os.environ['ROOT']); version=os.environ['VERSION']; dist=root/'dist'; fixed=(2026,1,1,0,0,0)
def add(zf,name,data,mode=0o644):
    info=ZipInfo(name,fixed); info.compress_type=ZIP_DEFLATED; info.create_system=3; info.external_attr=(mode&0xffff)<<16; zf.writestr(info,data)
def build(src,out):
    with ZipFile(out,'w') as zf:
        for p in sorted(x for x in src.rglob('*') if x.is_file()): add(zf,p.relative_to(src).as_posix(),p.read_bytes(),0o755 if p.name.endswith('.sh') else 0o644)
component=dist/f'com_xdecarotasks_{version}.zip'; plugin=dist/f'plg_task_xdecarotasks_{version}.zip'; package=dist/f'pkg_xdecarotasks_{version}.zip'
build(root/'component',component); build(root/'plugins/task/xdecarotasks',plugin)
with ZipFile(package,'w') as zf:
    for p in sorted(x for x in (root/'package/pkg_xdecarotasks').rglob('*') if x.is_file()): add(zf,p.relative_to(root/'package/pkg_xdecarotasks').as_posix(),p.read_bytes())
    add(zf,'com_xdecarotasks.zip',component.read_bytes()); add(zf,'plg_task_xdecarotasks.zip',plugin.read_bytes())
required={component:{'xdecarotasks.xml','admin/services/provider.php','admin/sql/install.mysql.utf8mb4.sql','admin/src/Service/TaskService.php','admin/src/Controller/TaskController.php'},plugin:{'xdecarotasks.xml','services/provider.php','src/Extension/Tasks.php','forms/due.xml'},package:{'pkg_xdecarotasks.xml','script.php','com_xdecarotasks.zip','plg_task_xdecarotasks.zip'}}
for archive,expected in required.items():
    with ZipFile(archive) as zf:
        missing=expected-set(zf.namelist()); bad=zf.testzip()
        if missing or bad: raise SystemExit(f'Invalid {archive.name}: missing={missing} bad={bad}')
assets=[component,plugin,package]; (dist/'SHA256SUMS.txt').write_text(''.join(f'{hashlib.sha256(a.read_bytes()).hexdigest()}  {a.name}\n' for a in assets),encoding='utf-8')
PY
printf 'Built Tasks by xdecaro %s\n' "$VERSION"; cat "$DIST/SHA256SUMS.txt"
