<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

class PartnerWalletController extends PartnerBaseController
{
    public function index()
    {
        $partner = $this->partner();

        return view('monetization::partner.wallet', [
            'partner' => $partner,
            'balance' => $partner->walletBalance(),
            'entries' => $partner->walletEntries()
                ->with('createdBy:id,username')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }
}
