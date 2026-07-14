{{--
    "About <VJ>" card for the foot of the VJ hub page.

    Why it matters beyond looking nice: before this, every VJ page on the site
    contained zero unique prose — I pulled the body text off the live
    /vj/vj-junior and got back nothing but leaked CSS. The pages were poster
    grids, near-identical to one another, which is the thin-content /
    template-clone problem, and it only gets worse once VJ x genre pages sit on
    top. This card is where a VJ page earns the right to be its own page.

    The social links are the SEO payload: they render as real anchors here and,
    in parallel, feed schema.org `sameAs` on the Person node (see
    Modules\Seo\app\Support\StructuredData::vjPerson) — the signal that ties this
    VJ Junior to the VJ Junior with an existing audience on YouTube/TikTok.

    Renders NOTHING when there is nothing to show. An empty "About" shell on 36
    pages is the same thin-content problem in nicer packaging.

    Layout: photo + name + socials form the header row; the bio runs full-width
    beneath it. With a real SEO description (several hundred chars) a bio squeezed
    into a column beside an avatar reads badly — full width keeps the measure
    readable.

    Theme: authored dark-first because the frontend runs dark
    (data-bs-theme="dark") and its own surfaces are ~#191919; Bootstrap's
    --bs-secondary-bg token resolves light in this theme and rendered the card as
    a pale bar, so colours are explicit here. A prefers-color-scheme override
    handles a light context.

    Props:
        vj — Vj model
--}}
@php
    $bio = trim(strip_tags((string) $vj->description));

    // Only real, absolute links. Columns store null for blanks (VjController
    // normalises '' -> null), but stay defensive — a relative/empty href would
    // render as a dead link.
    $socials = [];
    foreach (\Modules\Content\app\Models\Vj::SOCIAL_FIELDS as $field => $label) {
        $url = trim((string) ($vj->{$field} ?? ''));
        if ($url !== '' && preg_match('#^https?://#i', $url)) {
            $socials[$field] = ['label' => $label, 'url' => $url];
        }
    }

    $icons = [
        'youtube_url'   => 'ph-youtube-logo',
        'tiktok_url'    => 'ph-tiktok-logo',
        'facebook_url'  => 'ph-facebook-logo',
        'instagram_url' => 'ph-instagram-logo',
        'website_url'   => 'ph-globe-simple',
    ];

    $hasPhoto = !empty($vj->photo_url);
    $hasCard  = $hasPhoto || $bio !== '' || $socials !== [];
@endphp

@if ($hasCard)
    <section class="vj-about mt-5" aria-labelledby="vj-about-heading">
        <article class="vj-about__card">
            <header class="vj-about__header">
                @if ($hasPhoto)
                    <img class="vj-about__photo"
                        src="{{ media_img($vj->photo_url, 220) }}"
                        srcset="{{ media_srcset($vj->photo_url, [110, 220]) }}"
                        sizes="110px"
                        alt="{{ $vj->display_name }}"
                        width="110" height="110"
                        loading="lazy" decoding="async">
                @endif

                <div class="vj-about__identity">
                    <span class="vj-about__eyebrow">
                        <i class="ph ph-microphone-stage"></i> Narrated by
                    </span>
                    <h2 class="vj-about__name" id="vj-about-heading">{{ $vj->display_name }}</h2>
                    <span class="vj-about__role">Video Jockey · Luganda Film Translation</span>
                </div>

                @if ($socials !== [])
                    <ul class="vj-about__socials">
                        @foreach ($socials as $field => $social)
                            <li>
                                {{-- rel="noopener": off-site, opens in a new tab; without
                                     it the destination gets a handle on our window. --}}
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                    class="vj-about__social vj-about__social--{{ \Illuminate\Support\Str::before($field, '_') }}"
                                    aria-label="{{ $vj->display_name }} on {{ $social['label'] }}"
                                    title="{{ $social['label'] }}">
                                    <i class="ph {{ $icons[$field] ?? 'ph-link-simple' }}"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </header>

            @if ($bio !== '')
                <p class="vj-about__bio">{{ $bio }}</p>
            @endif
        </article>
    </section>

    <style>
        .vj-about { max-width: 760px; }

        .vj-about__card {
            padding: 1.5rem 1.6rem;
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 1rem;
            background:
                radial-gradient(120% 140% at 0% 0%, rgba(26, 152, 255, .10) 0%, rgba(26, 152, 255, 0) 42%),
                #16161a;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 12px 32px -18px rgba(0, 0, 0, .8);
        }

        .vj-about__header {
            display: flex;
            align-items: center;
            gap: 1.15rem;
        }

        .vj-about__photo {
            flex: 0 0 auto;
            width: 92px;
            height: 92px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, .12);
            box-shadow: 0 0 0 3px rgba(26, 152, 255, .18);
        }

        .vj-about__identity {
            flex: 1 1 auto;
            min-width: 0;
        }

        .vj-about__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--bs-primary, #1A98FF);
        }

        .vj-about__name {
            margin: .18rem 0 .1rem;
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.15;
            color: #fff;
        }

        .vj-about__role {
            display: block;
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
        }

        .vj-about__socials {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin: 0;
            padding: 0;
            list-style: none;
            align-self: flex-start;
        }

        .vj-about__social {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 1.15rem;
            text-decoration: none;
            color: rgba(255, 255, 255, .82);
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .12);
            transition: transform .15s ease, background-color .15s ease, color .15s ease, border-color .15s ease;
        }
        .vj-about__social:hover {
            transform: translateY(-2px);
            color: #fff;
        }
        /* Brand-recognisable hover colours — a professional social row reads as
           the platforms it links to, not five identical grey dots. */
        .vj-about__social--youtube:hover   { background: #FF0000; border-color: #FF0000; }
        .vj-about__social--tiktok:hover    { background: #FE2C55; border-color: #FE2C55; }
        .vj-about__social--facebook:hover  { background: #1877F2; border-color: #1877F2; }
        .vj-about__social--instagram:hover { background: #E4405F; border-color: #E4405F; }
        .vj-about__social--website:hover    { background: var(--bs-primary, #1A98FF); border-color: var(--bs-primary, #1A98FF); }

        .vj-about__bio {
            margin: 1.15rem 0 0;
            padding-top: 1.15rem;
            border-top: 1px solid rgba(255, 255, 255, .07);
            font-size: .92rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, .68);
        }

        /* Light context (frontend is dark by default; respect a light theme). */
        @media (prefers-color-scheme: light) {
            :root:not([data-bs-theme="dark"]) .vj-about__card {
                background: #ffffff;
                border-color: rgba(0, 0, 0, .08);
                box-shadow: 0 1px 2px rgba(0, 0, 0, .05), 0 12px 30px -20px rgba(0, 0, 0, .3);
            }
            :root:not([data-bs-theme="dark"]) .vj-about__name { color: #14151a; }
            :root:not([data-bs-theme="dark"]) .vj-about__role { color: rgba(0, 0, 0, .5); }
            :root:not([data-bs-theme="dark"]) .vj-about__bio {
                color: rgba(0, 0, 0, .66);
                border-top-color: rgba(0, 0, 0, .08);
            }
            :root:not([data-bs-theme="dark"]) .vj-about__social {
                color: rgba(0, 0, 0, .68);
                background: rgba(0, 0, 0, .03);
                border-color: rgba(0, 0, 0, .12);
            }
        }

        /* Tablet/phone: photo + identity stay on one row, socials drop below
           the name so the header never crushes. */
        @media (max-width: 575.98px) {
            .vj-about { max-width: none; }
            .vj-about__card { padding: 1.25rem; }
            .vj-about__header { flex-wrap: wrap; }
            .vj-about__photo { width: 72px; height: 72px; }
            .vj-about__name { font-size: 1.2rem; }
            .vj-about__socials {
                flex-basis: 100%;
                margin-top: .35rem;
            }
        }
    </style>
@endif
