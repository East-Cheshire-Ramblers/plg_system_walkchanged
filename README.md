# plg_system_walkchanged

A simple Joomla system plugin to configure changing the look of an edited walk.

It highlights rendered Ramblers walk titles that contain a configurable marker such as `***`, while skipping cancelled walks.

## Behaviour

- Looks at the final rendered site HTML on any site page or article.
- Always loads a browser-side pass for programme pages that populate walks after page load.
- Avoids fragile browser regex escaping so Joomla optimization plugins can safely process the script.
- Finds rendered walk items containing the marker, defaulting to `***`.
- Moves the marker to the start of the walk text, before the date.
- Skips items whose nearby rendered text contains `cancelled` or `canceled`.
- Also skips struck-through items.
- Applies the configured colour to the leading changed-walk text, including the date and title.
- Colours the programme-page changed-walk row text after the grade icon, including date, title, distance, and contact.

## Install

Zip the contents of `plg_system_walkchanged` and install the zip in Joomla:

```sh
cd plg_system_walkchanged
zip -r ../plg_system_walkchanged.zip .
```

Then in Joomla:

1. Go to **System > Install > Extensions**.
2. Upload `plg_system_walkchanged.zip`.
3. Go to **System > Manage > Plugins**.
4. Enable **System - Walk Changed Highlighter**.
5. Configure the marker and colour.

## Useful Settings

- **Changed marker**: `***`
- **Highlight colour**: any hex colour, defaulting to the East Cheshire logo orange `#F08050`

The marker remains visible because the website key uses it to denote changed walk details.
