{{-- Every Visible Home category shelf, as one block.

     One rail per category the admin flagged "Visible Home", in the sort order
     set by dragging on the Categories screen. Data comes from
     HomeRailsService: each category carries `railItems` — published movies +
     series merged, newest first, max 12, tagged `_isShow` for per-card
     routing. Rail markup is partials.category-rail.

     Three of these used to be scattered across the page at fixed slots,
     standing in for rails that had been retired. Those rails are back and the
     arrangement screen is where a shelf is moved now, so the block is whole
     and it moves as one section. --}}
@foreach (($homeCategories ?? collect()) as $cat)
    @include('frontend::components.partials.category-rail', ['cat' => $cat])
@endforeach
