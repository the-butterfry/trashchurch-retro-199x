#!/usr/bin/env python3
import argparse
import re
from pathlib import Path
from datetime import datetime

AUTO_BLOCK_START = "/* BEGIN AUTO-CLEAN HEADER BLOCK */"
AUTO_BLOCK_END = "/* END AUTO-CLEAN HEADER BLOCK */"

AUTO_BLOCK_CSS = f"""
{AUTO_BLOCK_START}

/* === Header cleanup (consolidated) === */

/* Header fonts (single source of truth) */
.tr-title,
.tr-title a {{
  font-family: var(--tr-header-font, var(--tr-font));
}}
.tr-tagline {{
  font-family: var(--tr-tagline-font, var(--tr-header-font, var(--tr-font)));
}}

/* Images flanking the title (frameless) */
.tr-title-img {{
  display: block;
  width: clamp(48px, 8vw, 120px);
  height: auto;
  max-height: 18vh;
  object-fit: contain;
  image-rendering: pixelated;
  pointer-events: none;
  user-select: none;
  transform: translateY(0);
  transition: transform .18s ease;

  /* frameless */
  background: transparent;
  border: 0;
  border-radius: 0;
  box-shadow: none;
}}

/* Title/Tagline vertical stack (centered) */
.tr-title-stack {{
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
}}

/* Title centered */
.tr-title {{
  text-align: center;
  margin: 0;
}}

/* Tagline under the title, shifted 10px to the right */
.tr-tagline {{
  margin: 0 0 0 10px;
  text-align: center;
  transform: none;
  white-space: normal;
}}

{AUTO_BLOCK_END}
""".strip() + "\n"


def remove_pattern(text: str, pattern: str, desc: str, flags=re.DOTALL | re.IGNORECASE):
    new_text, count = re.subn(pattern, "", text, flags=flags)
    return new_text, count, desc


def ensure_auto_block(text: str) -> str:
    # Remove any previous auto block
    text = re.sub(
        re.escape(AUTO_BLOCK_START) + r".*?" + re.escape(AUTO_BLOCK_END),
        "",
        text,
        flags=re.DOTALL,
    )
    # Append fresh block at end with a leading newline
    if not text.endswith("\n"):
        text += "\n"
    return text + "\n" + AUTO_BLOCK_CSS


def main():
    parser = argparse.ArgumentParser(description="Clean duplicate/conflicting header CSS and inject a consolidated block.")
    parser.add_argument("css_path", type=Path, help="Path to style.css")
    parser.add_argument("--dry-run", action="store_true", help="Show what would change without writing files")
    args = parser.parse_args()

    css_path: Path = args.css_path
    if not css_path.exists():
        print(f"ERROR: File not found: {css_path}")
        return

    original = css_path.read_text(encoding="utf-8", errors="ignore")
    text = original

    changes = []

    # 1) Remove any .tr-title-stack block that forces a row layout
    text, c, d = remove_pattern(
        text,
        r"""(?s)\.tr-title-stack\s*\{[^}]*?\bflex-direction\s*:\s*row\s*;?[^}]*?\}""",
        ".tr-title-stack with flex-direction: row",
    )
    changes.append((d, c))

    # 2) Remove tagline alignment block that drops/offsets tagline (translateY(5px) or right-align+nowrap)
    text, c1, d1 = remove_pattern(
        text,
        r"""(?s)\.tr-tagline\s*\{[^}]*?translateY\s*\(\s*5px\s*\)[^}]*?\}""",
        ".tr-tagline block with translateY(5px)",
    )
    text, c2, d2 = remove_pattern(
        text,
        r"""(?s)\.tr-tagline\s*\{(?=[^}]*?(?:text-align\s*:\s*right|white-space\s*:\s*nowrap))[^}]*\}""",
        ".tr-tagline block with right-align/nowrap",
    )
    changes.append((d1, c1))
    changes.append((d2, c2))

    # 3) Remove any .tr-title-wrap scoped overrides we previously added in chat
    for sel in [
        r"\.tr-title-wrap\s+\.tr-title-stack",
        r"\.tr-title-wrap\s+\.tr-title",
        r"\.tr-title-wrap\s+\.tr-tagline",
    ]:
        text, c, d = remove_pattern(
            text, rf"(?s){sel}\s*\{{[^}}]*\}}", f"remove override block: {sel}"
        )
        changes.append((d, c))

    # 4) Remove universal font reset blocks
    text, c, d = remove_pattern(
        text,
        r"""(?s)\*\s*,\s*\*::before\s*,\s*\*::after\s*\{[^}]*?\bfont-family\b[^}]*\}""",
        "universal font reset (*, *::before, *::after)",
    )
    changes.append((d, c))

    # 5) Remove all @font-face blocks for Comic Sans MS (optional cleanup)
    text, c, d = remove_pattern(
        text,
        r"""(?s)@font-face\s*\{[^}]*?\bfont-family\s*:\s*["']Comic Sans MS["'][^}]*\}""",
        '@font-face "Comic Sans MS"',
    )
    changes.append((d, c))

    # 6) Remove duplicate font variable blocks for title/tagline so we can add one canonical block
    text, c1, d1 = remove_pattern(
        text,
        r"""(?s)\.tr-title\s*,\s*\.tr-title\s*a\s*\{[^}]*?\bfont-family\b[^}]*\}""",
        "duplicate .tr-title/.tr-title a font-family blocks",
    )
    text, c2, d2 = remove_pattern(
        text,
        r"""(?s)\.tr-tagline\s*\{[^}]*?\bfont-family\b[^}]*\}""",
        "duplicate .tr-tagline font-family blocks",
    )
    changes.append((d1, c1))
    changes.append((d2, c2))

    # 7) Remove framed .tr-title-img block(s) only if they include frame props (preserve responsive display:none rule)
    text, c, d = remove_pattern(
        text,
        r"""(?s)\.tr-title-img\s*\{(?=[^}]*?(?:box-shadow|border-radius|border\s*:|background\s*:))[^}]*\}""",
        ".tr-title-img blocks with frame styling",
    )
    changes.append((d, c))

    # 8) Remove any auto block from previous runs and append fresh one
    text = ensure_auto_block(text)

    # 9) Optional: collapse excessive blank lines created by deletions
    text = re.sub(r"\n{3,}", "\n\n", text).strip() + "\n"

    # Report summary
    print("Cleanup summary:")
    for desc, count in changes:
        print(f"- {desc}: removed {count}")

    if args.dry_run:
        print("\n-- DRY RUN: no files written --")
        if text != original:
            print(f"Would modify: {css_path}")
        else:
            print("No changes would be made.")
        return

    # Write backup
    backup_path = css_path.with_suffix(css_path.suffix + f".bak-{datetime.utcnow().strftime('%Y%m%d%H%M%S')}")
    backup_path.write_text(original, encoding="utf-8")
    print(f"Backup written: {backup_path.name}")

    # Write updated CSS
    css_path.write_text(text, encoding="utf-8")
    print(f"Updated CSS written: {css_path}")


if __name__ == "__main__":
    main()
