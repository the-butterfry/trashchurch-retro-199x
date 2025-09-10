# TrashChurch Retro 199X

Maximalist late 90s Geocities/Angelfire style WordPress theme with full modern responsiveness and accessibility touches, plus a built‑in retro Hit Counter (global + per-post), comet cursor, marquee, and optional MIDI.

This repo contains the theme in `trashchurch-retro-199x/` and a GitHub Action that packages a ready-to-install ZIP whenever you publish a Release.

## Quick Start

1. Download the latest Release ZIP.
2. Upload the ZIP to WordPress: Appearance → Themes → Add New → Upload Theme.
3. Activate the theme and open Appearance → Customize → Retro 199X Options.

## Development

- Theme source lives in: `trashchurch-retro-199x/`
- License: GPL-2.0-or-later

### Release workflow

- Publish a Release (e.g., tag `v0.2.0`).
- The workflow zips `trashchurch-retro-199x/` into `trashchurch-retro-199x-<tag>.zip` and attaches it to the Release automatically.

## Directory layout

```
/
├─ .github/workflows/release.yml    # build-and-attach ZIP on Release
├─ LICENSE                          # GPL-2.0-or-later
├─ README.md                        # this file
└─ trashchurch-retro-199x/          # the theme (style.css, functions.php, templates, assets, etc.)
```

For full theme docs (features and options), see `trashchurch-retro-199x/readme.md`.
