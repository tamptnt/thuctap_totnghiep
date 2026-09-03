<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Exception;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    public function render($request, Exception|Throwable $e)
    {
        if($this->isHttpException($e)) {
            switch ($e->getStatusCode()) {
                // not found
                case 403:
                case 404:
                    return redirect('/errors/404');

                // internal error
                // case '500':
                // return redirect('/');
                // break;

                default:
                    return $this->renderHttpException($e);
            }
        }
        else
        {
                return parent::render($request, $e);
        }
    }
}
