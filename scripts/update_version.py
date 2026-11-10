import os
import re
import subprocess
import sys
from pathlib import Path

# ----------------------------------------------------------------------
# 1. PARSE ARGUMENTS (Commit message & Version)
# ----------------------------------------------------------------------
default_msg = "Fixed Stats + Export Submission"
default_ver = "1.0.004"

commit_msg = default_msg
target_version = default_ver

# Supports semantic and zero-padded versions (e.g., 1.0.004, 3.0.919)
version_pattern = re.compile(r"^v?(\d+(?:\.\d+)+)$")
args = sys.argv[1:]

if len(args) == 1:
    matched = version_pattern.match(args[0].strip())
    if matched:
        target_version = matched.group(1)
    else:
        commit_msg = args[0].strip()
elif len(args) >= 2:
    m1 = version_pattern.match(args[0].strip())
    m2 = version_pattern.match(args[1].strip())
    if m2:
        commit_msg = args[0].strip()
        target_version = m2.group(1)
    elif m1:
        target_version = m1.group(1)
        commit_msg = args[1].strip()
    else:
        commit_msg = args[0].strip()
        target_version = args[1].strip()

target_version = target_version.lstrip("v")
tag_name = f"working-stable-v{target_version}"
branch_backup = f"backup/{tag_name}"

# Locate Pro repository root
repo_dir = Path(__file__).resolve().parent.parent
if not (repo_dir / "wppoppop-pro.php").exists():
    repo_dir = Path.home() / "Desktop" / "wppoppop-pro"

plugin_file = repo_dir / "wppoppop-pro.php"
readme_file = repo_dir / "readme.txt"

print(f"=== [WPPOPPOP PRO] SYNCHRONIZING TO VERSION {target_version} ===")
print(f"=== [WPPOPPOP PRO] COMMIT MESSAGE: '{commit_msg}' ===")

# ----------------------------------------------------------------------
# 2. UPDATE wppoppop-pro.php (Header Version & WPPOPPOP_PRO_VERSION)
# ----------------------------------------------------------------------
if not plugin_file.exists():
    print(f"[ERROR] Main plugin file not found: {plugin_file}")
    sys.exit(1)

php_content = plugin_file.read_text(encoding="utf-8")
php_content = re.sub(
    r'(\*\s*Version:\s*)[^\r\n]+',
    rf'\g<1>{target_version}',
    php_content,
    flags=re.IGNORECASE
)
php_content = re.sub(
    r"(define\(\s*['\"]WPPOPPOP_PRO_VERSION['\"]\s*,\s*['\"])[^'\"]+(['\"]\s*\);)",
    rf'\g<1>{target_version}\2',
    php_content
)
plugin_file.write_text(php_content, encoding="utf-8")
print(f"[UPDATED] wppoppop-pro.php -> {target_version}")

# ----------------------------------------------------------------------
# 3. UPDATE readme.txt (Stable tag & Changelog Header)
# ----------------------------------------------------------------------
if readme_file.exists():
    r_content = readme_file.read_text(encoding="utf-8")
    r_content = re.sub(
        r'(Stable tag:\s*)[^\r\n]+',
        rf'\g<1>{target_version}',
        r_content,
        flags=re.IGNORECASE
    )
    r_content = re.sub(
        r'(= )(\d+(?:\.\d+)+)( =)',
        rf'\g<1>{target_version}\g<3>',
        r_content,
        count=1
    )
    readme_file.write_text(r_content, encoding="utf-8")
    print(f"[UPDATED] readme.txt -> {target_version}")
else:
    print("[WARNING] readme.txt not found, skipping.")

# ----------------------------------------------------------------------
# 4. GIT AUTOMATION PIPELINE
# ----------------------------------------------------------------------
def run_git(cmd_args, allow_fail=False):
    res = subprocess.run(["git"] + cmd_args, cwd=repo_dir, capture_output=True, text=True)
    if res.returncode != 0 and not allow_fail:
        print(f"[GIT ERROR] Command 'git {' '.join(cmd_args)}' failed:\n{res.stderr.strip() or res.stdout.strip()}")
        sys.exit(1)
    return res

print("\n=== [WPPOPPOP PRO] RUNNING GIT PIPELINE ===")

run_git(["init"])
print("[OK] git init")

remotes = run_git(["remote"], allow_fail=True).stdout
if "origin" not in remotes.split():
    run_git(["remote", "add", "origin", "https://github.com/cygnusorbit/wppoppop-pro.git"])
    print("[OK] Linked origin -> https://github.com/cygnusorbit/wppoppop-pro.git")

run_git(["branch", "-M", "main"])

run_git(["add", "-A"])
print("[OK] git add -A")

commit_res = run_git(["commit", "-m", commit_msg], allow_fail=True)
if commit_res.returncode == 0:
    print(f"[OK] git commit -m '{commit_msg}'")
elif "nothing to commit" in commit_res.stdout or "nothing to commit" in commit_res.stderr:
    print(f"[INFO] Working tree clean; commit '{commit_msg}' skipped.")

run_git(["tag", "-a", "-f", tag_name, "-m", "Backup"])
print(f"[OK] git tag -a {tag_name} -m 'Backup'")

run_git(["branch", "-f", branch_backup])
print(f"[OK] git branch {branch_backup}")

print("[PUSHING] git push -u origin main --force ...")
push_res = run_git(["push", "-u", "origin", "main", "--force"])
if push_res.returncode == 0:
    print("[SUCCESS] Pushed cleanly to origin/main.")
else:
    print(f"[PUSH OUTPUT]\n{push_res.stdout.strip()}\n{push_res.stderr.strip()}")

print(f"\n[COMPLETE] WpPopPop Pro v{target_version} updated, committed as '{commit_msg}', tagged, and pushed.")
