{**
 * accessDenied.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View.
 *
 * $Id$
 *}
{assign var="pageTitle" value="schedConf.presentations.short"}{include file="common/header.tpl"}

<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>
<div><i>{$paper->getPresenterString()|escape}</i></div>
<br />

<p>{translate key="reader.accessDenied"}</p>

{include file="common/footer.tpl"}
