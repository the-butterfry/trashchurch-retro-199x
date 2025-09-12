#!/usr/bin/env python3
import argparse
import re
from pathlib import Path
from datetime import datetime

def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="ignore")

def write_text(path: Path, text: str):
    path.write_text(text, encoding="utf-8")

def backup_file(path: Path) -> Path:
    backup = path.with_suffix(path.suffix + f".bak-{datetime.utcnow().strftime('%Y%m%d%H%M%S')}")
    write_text(backup, read_text(path))
    return backup

def remove_pattern_once(text: str, pattern: str, desc: str, flags=re.DOTALL|re.IGNORECASE):
    new_text, count = re.subn(pattern, "", text, count=1, flags=flags)
    return new_text, min(count, 1), desc

def remove_pattern_all(text: str, pattern: str, desc: str, flags=re.DOTALL|re.IGNORECASE):
    new_text, count = re.subn(pattern, "", text, flags=flags)
    return new_text, count, desc

def remove_block_if_contains_props(text: str, selector: str, must_contain: list, desc: str):
    # Remove the whole block for selector if it contains all props (any order)
    # selector is a regex for the selector portion (already escaped as needed)
    pattern = rf"(?s){selector}\s*\{{([^}}]*?)\}}"
    out = text
    removed = 0
    def repl(m):
        nonlocal removed
        body = m.group(1)
        if all(re.search(prop, body, flags=re.IGNORECASE) for prop in must_contain):
            removed += 1
            return ""  # drop entire block
        return m.group(0)
    out = re.sub(pattern, repl, text)
    return out, removed, desc

def remove_duplicates_keep_first(text: str, pattern: str, desc: str, flags=re.DOTALL|re.IGNORECASE):
    # Find all matches; keep the first; remove subsequent
    matches = list(re.finditer(pattern, text, flags))
    if len(matches) <= 1:
        return text, 0, desc
    # Build segments excluding later matches
    remove_spans = [ (m.start(), m.end()) for m in matches[1:] ]
    new_text = []
    last = 0
    for s,e in remove_spans:
        new_text.append(text[last:s])
        last = e
    new_text.append(text[last:])
    return "".join(new_text), len(matches)-1, desc

def fix_footer_badge_bg(text: str):
    # Only fix inside .tr-footer .tr-badges img block
    block_pat = r"(?s)(\.tr-footer\s+\.tr-badges\s+img\s*\{)([^}]*)(\})"
    def repl(m):
        head, body, tail = m.groups()
        fixed_body, n = re.subn(r"background\s*:\s*#0\s*;", "background: #000;", body, flags=re.IGNORECASE)
        return head + fixed_body + tail, n
    total = 0
    def repl_wrapper(m):
        nonlocal total
        fixed, n = repl(m)
        total += n
        return fixed
    new_text = re.sub(block_pat, repl_wrapper, text)
    return new_text, total, "Fix background: #0; -> #000; in .tr-footer .tr-badges img"

def main():
    ap = argparse.ArgumentParser(description="Phase-2 style.css cleanup: remove conflicting header overrides, dedupe hamburger/monospace blocks, fix footer bg.")
    ap.add_argument("css_path", type=Path, help="Path to style.css")
    ap.add_argument("--dry-run", action="store_true", help="Preview changes without writing")
    args = ap.parse_args()

    if not args.css_path.exists():
        print(f"ERROR: File not found: {args.css_path}")
        return

    original = read_text(args.css_path)
    text = original
    changes = []

    # 1) Remove conflicting left-aligned header overrides (with !important)
    text, c, d = remove_block_if_contains_props(
        text,
        r"\.tr-title-stack",
        [r"flex-direction\s*:\s*column\s*!important", r"align-items\s*:\s*flex-start\s*!important"],
        "Remove left-aligned .tr-title-stack override with !important"
    )
    changes.append((d, c))

    text, c, d = remove_block_if_contains_props(
        text,
        r"\.tr-title",
        [r"text-align\s*:\s*left\s*!important"],
        "Remove .tr-title { text-align:left !important; } block"
    )
    changes.append((d, c))

    text, c, d = remove_block_if_contains_props(
        text,
        r"\.tr-tagline",
        [r"margin\s*:\s*0\s*0\s*0\s*10px\s*!important", r"text-align\s*:\s*left\s*!important", r"transform\s*:\s*none\s*!important"],
        "Remove .tr-tagline left-align/margin override with !important"
    )
    changes.append((d, c))

    # 2) Remove the inline-block/vertical-align tweak on .tr-title
    text, c, d = remove_pattern_all(
        text,
        r"(?s)\.tr-title\s*\{[^}]*?\bdisplay\s*:\s*inline-block\b[^}]*?\bvertical-align\s*:\s*bottom\b[^}]*?\}",
        "Remove .tr-title { display:inline-block; vertical-align: bottom; }"
    )
    changes.append((d, c))

    # 3) Deduplicate monospace restore block (keep first)
    mono_block_pat = r"(?s)code\s*,\s*pre\s*,\s*\.tr-mono\s*,\s*\.tr-nav\s+li\s+a\s*,\s*\.tr-sidebar\s+\.widget-title\s*\{[^}]*?\}"
    text, c, d = remove_duplicates_keep_first(text, mono_block_pat, "Deduplicate monospace restore block (keep first)")
    changes.append((d, c))

    # 4) Deduplicate hamburger/toggle blocks (keep first of each selector/media rule)
    hamburger_patterns = [
        r"(?s)\.tr-nav-toggle\s*\{[^}]*?\}",
        r"(?s)\.tr-nav-toggle\s+\.tr-hamburger\s*\{[^}]*?\}",
        r"(?s)\.tr-nav-toggle\s+\.tr-hamburger::before\s*,\s*\.tr-nav-toggle\s+\.tr-hamburger::after\s*\{[^}]*?\}",
        r"(?s)@media\s*\(max-width:\s*980px\)\s*\{\s*[^{}]*?\.tr-nav-toggle\s*\{[^}]*?\}[^}]*?\}",
        r"(?s)@media\s*\(min-width:\s*981px\)\s*\{\s*[^{}]*?\.tr-nav-toggle\s+\.tr-hamburger\s*\{[^}]*?\}[^}]*?\}",
        r"(?s)\.tr-nav-toggle\[aria-expanded=\"true\"\]\s*\.tr-hamburger\s*\{[^}]*?\}",
        r"(?s)\.tr-nav-toggle\[aria-expanded=\"true\"\]\s*\.tr-hamburger::before\s*\{[^}]*?\}",
        r"(?s)\.tr-nav-toggle\[aria-expanded=\"true\"\]\s*\.tr-hamburger::after\s*\{[^}]*?\}",
    ]
    for pat in hamburger_patterns:
        text, c, d = remove_duplicates_keep_first(text, pat, f"Deduplicate hamburger rule: {pat}")
        changes.append((d, c))

    # 5) Remove the duplicate z-index ordering block that uses !important (keep earlier non-important one)
    text, c, d = remove_pattern_all(
        text,
        r"(?s)\.tr-header::before\s*\{\s*[^}]*?\bz-index\s*:\s*0\s*!important\s*;?[^}]*?\}",
        "Remove duplicate .tr-header::before with z-index:0 !important"
    )
    changes.append((d, c))
    text, c, d = remove_pattern_all(
        text,
        r"(?s)\.tr-title\s*,\s*\.tr-title\s*a\s*,\s*\.tr-tagline\s*\{\s*[^}]*?\bz-index\s*:\s*2\s*!important\s*;?[^}]*?\}",
        "Remove duplicate .tr-title/.tr-tagline z-index:2 !important block"
    )
    changes.append((d, c))

    # 6) Fix background: #0; -> #000; in footer badges img
    text, c, d = fix_footer_badge_bg(text)
    changes.append((d, c))

    # 7) Collapse excessive blank lines
    text = re.sub(r"\n{3,}", "\n\n", text).strip() + "\n"

    # Report
    print("Cleanup summary:")
    total_removed = 0
    for desc, count in changes:
        print(f"- {desc}: {count}")
        total_removed += count

    if args.dry_run:
        print("\n-- DRY RUN: no files written --")
        if text != original:
            print(f"Would modify: {args.css_path}")
        else:
            print("No changes would be made.")
        return

    # Write backup and updated file
    backup = backup_file(args.css_path)
    print(f"Backup written: {backup}")
    write_text(args.css_path, text)
    print(f"Updated CSS written: {args.css_path}")

if __name__ == "__main__":
    main()
