<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InvalidRequestTransitionException extends RuntimeException
{
    public function render(Request $request): RedirectResponse
    {
        return back()->withErrors(['status' => $this->getMessage()]);
    }
}
