<?xml version="1.0" encoding="UTF-8"?>
<!--
  Browser-side stylesheet for /sitemap.xml. Referenced from the XML
  via an <?xml-stylesheet?> processing instruction so a human visiting
  the URL gets a styled, scannable view of every published URL — same
  data Googlebot reads from the raw XML, just rendered as HTML by the
  browser's XSLT engine.

  Search engines ignore this file entirely; it's purely cosmetic.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:html="http://www.w3.org/TR/REC-html40">

    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" />

    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex,follow" />
                <title>XML Sitemap — Jambo Films</title>
                <style>
                    :root {
                        --primary: #1A98FF;
                        --primary-dark: #1380e0;
                        --bg: #0f0f15;
                        --surface: #181822;
                        --surface-2: #1f1f2c;
                        --text: #e7e9ee;
                        --text-muted: rgba(231, 233, 238, 0.55);
                        --border: rgba(255, 255, 255, 0.06);
                    }
                    * { box-sizing: border-box; }
                    html, body {
                        margin: 0;
                        padding: 0;
                        background: var(--bg);
                        color: var(--text);
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                                     Roboto, "Helvetica Neue", Arial, sans-serif;
                        font-size: 14px;
                        line-height: 1.5;
                    }
                    a { color: var(--primary); text-decoration: none; }
                    a:hover { text-decoration: underline; }
                    code {
                        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
                        font-size: 12.5px;
                        background: rgba(255, 255, 255, 0.06);
                        padding: 1px 6px;
                        border-radius: 4px;
                    }
                    .hero {
                        background: linear-gradient(135deg, var(--primary) 0%, #0a4d9c 100%);
                        color: #fff;
                        padding: 44px 24px 38px;
                    }
                    .hero-inner {
                        max-width: 1080px;
                        margin: 0 auto;
                    }
                    .hero h1 {
                        font-size: 30px;
                        font-weight: 700;
                        margin: 0 0 10px;
                        letter-spacing: -0.01em;
                    }
                    .hero p {
                        margin: 0;
                        font-size: 14.5px;
                        line-height: 1.6;
                        max-width: 760px;
                        opacity: 0.92;
                    }
                    .hero p a {
                        color: #fff;
                        text-decoration: underline;
                        text-underline-offset: 2px;
                    }
                    .container {
                        max-width: 1080px;
                        margin: 0 auto;
                        padding: 28px 24px 40px;
                    }
                    .meta-bar {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        margin-bottom: 16px;
                        font-size: 13px;
                        color: var(--text-muted);
                    }
                    .meta-bar strong {
                        color: var(--text);
                        font-weight: 600;
                    }
                    .badge {
                        display: inline-block;
                        background: rgba(26, 152, 255, 0.14);
                        color: var(--primary);
                        font-size: 12px;
                        font-weight: 500;
                        padding: 3px 10px;
                        border-radius: 999px;
                    }
                    table {
                        width: 100%;
                        background: var(--surface);
                        border-collapse: collapse;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
                    }
                    thead th {
                        background: var(--surface-2);
                        color: var(--primary);
                        text-align: left;
                        padding: 12px 16px;
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.04em;
                        border-bottom: 1px solid var(--border);
                    }
                    tbody td {
                        padding: 11px 16px;
                        border-bottom: 1px solid var(--border);
                        vertical-align: top;
                    }
                    tbody tr:last-child td { border-bottom: none; }
                    tbody tr:hover td { background: rgba(255, 255, 255, 0.02); }
                    td.url { word-break: break-all; }
                    td.url a { color: var(--primary); }
                    td.priority {
                        font-variant-numeric: tabular-nums;
                        color: var(--text-muted);
                    }
                    td.lastmod, td.freq {
                        white-space: nowrap;
                        color: var(--text-muted);
                        font-size: 12.5px;
                    }
                    .empty {
                        text-align: center;
                        padding: 56px 16px;
                        color: var(--text-muted);
                    }
                    .footer-note {
                        margin-top: 18px;
                        font-size: 12.5px;
                        color: var(--text-muted);
                        text-align: center;
                    }
                    @media (max-width: 720px) {
                        .hero { padding: 32px 18px 26px; }
                        .hero h1 { font-size: 24px; }
                        .container { padding: 18px 12px 32px; }
                        thead th, tbody td { padding: 9px 10px; font-size: 12.5px; }
                        td.lastmod, td.freq, td.priority {
                            font-size: 12px;
                        }
                    }
                </style>
            </head>
            <body>
                <header class="hero">
                    <div class="hero-inner">
                        <h1>XML Sitemap</h1>
                        <p>
                            This sitemap lists every published page on Jambo Films so search engines
                            like Google can discover and index our movies, series, episodes, and VJ
                            profiles. <a href="https://www.sitemaps.org/" target="_blank" rel="noopener">Learn more about XML sitemaps</a>.
                        </p>
                    </div>
                </header>
                <main class="container">
                    <div class="meta-bar">
                        <span>
                            This sitemap contains
                            <strong><xsl:value-of select="count(s:urlset/s:url)" /></strong> URLs.
                        </span>
                        <span class="badge">Generated by Jambo Films</span>
                    </div>

                    <xsl:choose>
                        <xsl:when test="count(s:urlset/s:url) &gt; 0">
                            <table>
                                <thead>
                                    <tr>
                                        <th>URL</th>
                                        <th>Last modified</th>
                                        <th>Change frequency</th>
                                        <th>Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <xsl:for-each select="s:urlset/s:url">
                                        <tr>
                                            <td class="url">
                                                <a href="{s:loc}" target="_blank" rel="noopener">
                                                    <xsl:value-of select="s:loc" />
                                                </a>
                                            </td>
                                            <td class="lastmod">
                                                <xsl:choose>
                                                    <xsl:when test="s:lastmod">
                                                        <xsl:value-of select="substring(s:lastmod, 1, 10)" />
                                                    </xsl:when>
                                                    <xsl:otherwise>—</xsl:otherwise>
                                                </xsl:choose>
                                            </td>
                                            <td class="freq">
                                                <xsl:choose>
                                                    <xsl:when test="s:changefreq">
                                                        <xsl:value-of select="s:changefreq" />
                                                    </xsl:when>
                                                    <xsl:otherwise>—</xsl:otherwise>
                                                </xsl:choose>
                                            </td>
                                            <td class="priority">
                                                <xsl:choose>
                                                    <xsl:when test="s:priority">
                                                        <xsl:value-of select="s:priority" />
                                                    </xsl:when>
                                                    <xsl:otherwise>—</xsl:otherwise>
                                                </xsl:choose>
                                            </td>
                                        </tr>
                                    </xsl:for-each>
                                </tbody>
                            </table>
                        </xsl:when>
                        <xsl:otherwise>
                            <div class="empty">
                                No URLs in the sitemap yet. Publish a movie, series, or episode to populate this list.
                            </div>
                        </xsl:otherwise>
                    </xsl:choose>

                    <p class="footer-note">
                        Source: <code>https://jambofilms.com/sitemap.xml</code> &#8212;
                        crawlers see the raw XML; you're seeing the styled view.
                    </p>
                </main>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
