{{--
  Why AI Smart Shuffle picked this title. One line, muted, truncated —
  it's a footnote under the card meta, not a headline. Included only when
  the recommender actually computed a reason (see card-style.blade.php).

  The sparkle icon is the only place the "AI" in the rail title is echoed
  at card level; it's what ties a pick back to the shelf that produced it.
--}}
<div class="d-flex align-items-center gap-1 mt-1 jambo-card-reason" title="{{ $cardReason }}">
  <i class="ph ph-sparkle font-size-12 text-primary flex-shrink-0"></i>
  <small class="font-size-12 text-truncate" style="opacity: .75;">{{ $cardReason }}</small>
</div>
