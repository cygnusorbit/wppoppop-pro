import argparse
import re
import sys
from pathlib import Path


def update_plugin_version(file_path: str, new_version: str) -> bool:
  path = Path(file_path)

  if not path.is_file():
    print(f"Error: File '{file_path}' not found.", file=sys.stderr)
    return False

  content = path.read_text(encoding="utf-8")

  # 1. Matches: " * Version: 1.0.0"
  header_pattern = r"(^\s*\*\s*Version:\s*)([^\r\n]+)"

  # 2. Matches: "define('WPPOPPOP_PRO_VERSION', '1.0.0');"
  const_pattern = (
      r"(define\(\s*['\"]WPPOPPOP_PRO_VERSION['\"]\s*,\s*['\"])([^'\"]+)(['\"]\s*\);)"
  )

  # Check matches before replacing
  header_found = re.search(header_pattern, content, flags=re.MULTILINE)
  const_found = re.search(const_pattern, content)

  if not header_found and not const_found:
    print(
        "Error: Could not find version header or WPPOPPOP_PRO_VERSION constant.",
        file=sys.stderr,
    )
    return False

  # Replace header version
  content, count_header = re.subn(
      header_pattern,
      rf"\g<1>{new_version}",
      content,
      count=1,
      flags=re.MULTILINE,
  )

  # Replace constant version
  content, count_const = re.subn(
      const_pattern, rf"\g<1>{new_version}\g<3>", content, count=1
  )

  path.write_text(content, encoding="utf-8")

  print(f"Success! Updated '{file_path}' to version {new_version}:")
  print(f"  - Header line updated: {count_header > 0}")
  print(f"  - Constant definition updated: {count_const > 0}")
  return True


def main():
  parser = argparse.ArgumentParser(
      description="Update version in WpPopPop Pro PHP plugin file."
  )
  parser.add_argument("version", help="The new version string (e.g., 1.0.1)")
  parser.add_argument(
      "--file",
      "-f",
      default="wppoppop-pro.php",
      help="Path to the plugin PHP file (default: wppoppop-pro.php)",
  )

  args = parser.parse_args()

  success = update_plugin_version(args.file, args.version)
  if not success:
    sys.exit(1)


if __name__ == "__main__":
  main()