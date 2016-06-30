## Summary Cards

Logged-in users can individually decide whether or not to display a summary card. The
setting `$GLOBALS['sucgEnabledForAnonUser']` is provided to disable Summary Cards
for anon users in general.

The setting for a user can be found under `Preference -> Appearance`.

![image](https://cloud.githubusercontent.com/assets/1245473/16182795/16d3e012-36aa-11e6-97fd-769500b7905a.png)

### Templates and content summaries

Summaries are enabled on a per namespace basis by assigning a template that generates
the content.

```
$GLOBALS['sucgEnabledNamespaceWithTemplate'] = array(
	NS_MAIN         => 'Hovercard',
	NS_HELP         => 'Hovercard',
	NS_FILE         => 'Hovercard-File',
	NS_CATEGORY     => 'Hovercard-Category',
	SMW_NS_PROPERTY => 'Hovercard-Property',
);
```

If the setting includes reference to `SMW_NS_PROPERTY` then it should be added after
the `enableSemantics` in order for namespaces to be registered appropriately.

Please also have a look at the [templates](02-template-examples.md) document.

## Legitimate links

Summary Cards uses some informed ruleset to match only legitimate links (external as well as
interwiki links are generally disabled) that can show summary cards, yet it
can happen that the rules missed a certain link type which then needs adjustments.

Summary cards on the `SMW_NS_PROPERTY` namespace are only displayed when no
other highlighter (e.g. from SMW core) is available to avoid competing tooltips.

## Cache

To avoid unnecessary API requests, a client can use the local cache with
`$GLOBALS['sucgTooltipRequestCacheLifetime']` defining the duration of how long
data are to be kept before a new request is made (set to `30 min` by
default, `false` to disable it).

While client-side browser caching may help to avoid repeated requests, a
similar request from a different browser would still cause a content parse.
To leverage on existing summaries that have been created by other users,
`$GLOBALS['sucgBackendParserCacheType']` can be set to create cacheable
server-side content without deploying a special infrastructure (using
squid or varnish).

If a subject (== hovered link) and/or its assigned template are
altered then related cache items are evicted in order for content to be
regenerated (content that is locally cached are exempted from such update).