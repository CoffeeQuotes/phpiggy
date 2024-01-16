<?php

declare(strict_types=1);

namespace App\Controllers;

use Framework\TemplateEngine;
use App\Services\{ReceiptService, TransactionService};

class ReceiptController
{
    public function __construct(
        private TemplateEngine $view,
        private TransactionService $transactionService,
        private ReceiptService $receiptService
    ) {
    }

    public function uploadView(array $params)
    {
        $transaction = $this->transactionService->getUserTransaction($params['transaction']);

        if (!$transaction) {
            redirectTo("/");
        }

        echo $this->view->render("receipts/create.php");
    }

    public function upload(array $params)
    {
        $transaction = $this->transactionService->getUserTransaction($params['transaction']);

        if (!$transaction) {
            redirectTo("/");
        }

        $receiptFile = $_FILES['receipt'] ?? null;
        $this->receiptService->validateFile($receiptFile);
        $this->receiptService->upload($receiptFile, $transaction['id']);
        redirectTo("/");
    }
}
