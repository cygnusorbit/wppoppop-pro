


import sys
import re
from pathlib import Path

target_version = sys.argv[1] if len(sys.argv) > 1 else "1.0.0"
plugin_file = Path.home() / "Desktop" / "wppoppop" / "wppoppop.php"

if not plugin_file.exists():
    print(f"Error: {plugin_file} not found")
    sys.exit(1)

content = plugin_file.read_text(encoding="utf-8")

# Update plugin header comment Version
content = re.sub(r'(\*\s*Version:\s*)[^\n]+', rf'\g<1>{target_version}', content)

# Update WPPOPPOP_VERSION PHP constant
content = re.sub(
    r"(define\(\s*['\"]WPPOPPOP_VERSION['\"]\s*,\s*['\"])[^'\"]+(['\"]\s*\);)",
    rf'\g<1>{target_version}\2',
    content
)

plugin_file.write_text(content, encoding="utf-8")
print(f"wppoppop version successfully updated to {target_version}")
