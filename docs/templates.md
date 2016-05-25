## Usage

It is recommended that templates responsible for creating summaries make use of
`{{int: ... }}` parser to allow the content to be displayed in an appropriate
user language.

SUC will provide the following parameters for convenience so templates can be
build more effectively.

- `{{{subject}}}`
- `{{{namespace}}}`
- `{{{isFile}}}`
- `{{{isProperty}}}`
- `{{{isCategory}}}`
- `{{{userLanguage}}}`
- `{{{pageContentLanguage}}}`

## Examples

The following examples contain simple template snippets that can be used in connection
with the SummaryCards extension and will require [Semantic MediaWiki][smw] to work as
expected.

### Files and images

Image display for when `{{{isFile}}}` returns true (because the subject
is a file page).

```
<includeonly><!-- IMAGE --><div style="float:right;">
{{#ifeq: {{{isFile}}} | true | [[{{{subject}}}|100px|thumb]] }}
</div></includeonly>

```

Query a `Has portrait` (defined as page type) property value and if available display it
as a thumbnail.

```
<includeonly><!-- IMAGE --><div style="float:right;">
{{#ifeq: {{#show: {{{subject}}} |?Has portrait |default=false}} | false | | [[{{#show: {{{subject}}} |?Has portrait|link=none|limit=1}}|100px|thumb]] }}
</div></includeonly>
```

### Descriptions

Display descriptions which are user language dependent. Using the help of the
[Monolingual text type][mono] it is possible to store and query arbitrary text in
different languages.

Query a `Has monolingual description` description for the given `{{{userLanguage}}}`.

```
<includeonly><!-- Check if description exists --><div style="font-size:small;line-height: 100%; min-width: 250px;  max-width: 250px;">{{#ifeq: {{#show: {{{subject}}} |?Has monolingual description|default=false}} | false| | <!-- Query description for specific user language (invert query!!) -->{{#ask:[[-Has monolingual description::{{{subject}}}]][[Language code::{{{userLanguage}}}]] |?Text |link=none |headers=hide |mainlabel=- }} }}
</div></includeonly>
```

### Metadata

```
<p>Modification date: <span style="word-wrap: break-word;  word-break: break-word;">{{#show: {{{subject}}} |?Modification date#LOCL }}</span></p>
```

[mono]: https://www.semantic-mediawiki.org/wiki/Help:Type_Monolingual_text
[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki
