#!/usr/bin/env python3
import os
import sys
from pathlib import Path


def main() -> int:
    if len(sys.argv) != 3:
        sys.stderr.write("Usage: _set_env_var.py <env_file> <key>\n")
        return 1

    env_file = Path(sys.argv[1])
    key = sys.argv[2]
    value = os.environ.get("VALUE", "")
    if not value:
        value = ""

    if env_file.exists():
        lines = env_file.read_text().splitlines()
    else:
        lines = []

    key_prefix = f"{key}="
    new_line = f'{key}="{value}"'
    updated = False
    result_lines = []

    for line in lines:
        if line.startswith(key_prefix):
            result_lines.append(new_line)
            updated = True
        else:
            result_lines.append(line)

    if not updated:
        result_lines.append(new_line)

    env_file.write_text("\n".join(result_lines) + "\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

