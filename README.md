# ra_tweaks

A Joomla system plugin for East Cheshire Ramblers site tweaks.

The first tweak highlights rendered Ramblers walk titles that contain a configurable marker such as `***`, while skipping cancelled walks.

## Behaviour

- Looks at the final rendered site HTML on any site page or article.
- Always loads a browser-side pass for programme pages that populate walks after page load.
- Avoids fragile browser regex escaping so Joomla optimization plugins can safely process the script.
- Finds rendered walk items containing the marker, defaulting to `***`.
- Moves the marker to the start of the walk text, before the date.
- Renders the marker as a small circular badge using the configured colour and white marker text.
- Skips items whose nearby rendered text contains `cancelled` or `canceled`.
- Also skips struck-through items.
- Applies the configured colour to the leading changed-walk text, including the date and title.
- Colours the programme-page changed-walk row text after the grade icon, including date, title, distance, and contact.
- Adds an **RA Tweaks** administrator menu item under **Components** that opens this plugin's settings.

## Install

Zip the contents of `plg_system_ra_tweaks` and install the zip in Joomla:

```sh
cd plg_system_ra_tweaks
zip -r ../ra_tweaks-1.1.7.zip .
```

Then in Joomla:

1. Go to **System > Install > Extensions**.
2. Upload `ra_tweaks-1.1.7.zip`.
3. Go to **System > Manage > Plugins**.
4. Enable **System - RA Tweaks**.
5. Configure the marker and colour.

## Updates

The plugin manifest registers a Joomla update server:

`https://raw.githubusercontent.com/East-Cheshire-Ramblers/ra_tweaks/main/updates/ra_tweaks.xml`

For each release, update `updates/ra_tweaks.xml` to the new version and make sure the `downloadurl` points to the matching GitHub release asset.

## Useful Settings

- **Changed marker**: `***`
- **Highlight colour**: any hex colour, defaulting to the East Cheshire logo orange `#F08050`

The marker remains visible because the website key uses it to denote changed walk details.
