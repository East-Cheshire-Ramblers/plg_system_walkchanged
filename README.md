# plg_system_walkchanged

A simple Joomla system plugin to configure changing the look of an edited walk.

It highlights rendered Ramblers walk titles that contain a configurable marker such as `***`, while skipping cancelled walks.

## Behaviour

- Looks at the final rendered site HTML.
- Finds configured title elements containing the marker, defaulting to `***`.
- Skips items whose nearby rendered text contains `cancelled` or `canceled`.
- Also skips struck-through items.
- Applies the configured colour to the matching title element.
- Optionally removes the marker from the title after highlighting.

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
- **Remove marker from title**: Yes if you want visitors to see only the colour, not `***`
- **Title selectors**: add the actual walk-title CSS class if your Ramblers output has one

If the plugin colours too much or too little, inspect the rendered walk title HTML and add the most specific class selector to **Title selectors**.
