<?php
declare(strict_types=1);

namespace Books\Profile\Services;

use ApplicationException;
use Books\Book\Models\AwardBook;
use Books\Orders\Models\Order;
use Books\Profile\Classes\Enums\OperationType;
use Books\Profile\Contracts\OperationHistoryService as OperationHistoryServiceContract;
use Books\Profile\Models\OperationHistory;
use Books\Profile\Models\Profile;
use Cms\Classes\Controller;
use Exception;
use October\Rain\Support\Facades\Twig;

class OperationHistoryService implements OperationHistoryServiceContract
{
    /**
     * @param Order $order
     *
     * @return void
     * @throws ApplicationException
     */
    public function addBalanceDeposit(Order $order): void
    {
        $user = $order->user;
        $depositAmount = $order->deposits->sum('amount');

        $params = [
            'depositAmount' => $depositAmount,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::DepositOnBalance,
            'message' => $this->prepareBody(OperationType::DepositOnBalance, $params),
        ]);
    }

    /**
     * @param Order $order
     *
     * @return void
     * @throws ApplicationException
     */
    public function addReceivingCertificateAnonymous(Order $order): void
    {
        $user = $order->user;
        $depositAmount = $order->deposits->sum('amount');

        $params = [
            'depositAmount' => $depositAmount,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::TransferOnBalance,
            'message' => $this->prepareBody(OperationType::TransferOnBalance, $params),
        ]);
    }

    public function addWithdrawal(Order $order): void
    {
        // TODO: Implement addWithdrawal() method.
    }

    /**
     * @param Order $order
     * @param mixed $orderProduct
     *
     * @return void
     * @throws ApplicationException
     */
    public function addReceivingPurchase(Order $order, mixed $orderProduct): void
    {
        $user = $order->user;
        $product = $orderProduct->orderable;
        $edition = $product::class::find($product->id);

        $params = [
            'url' => url('book-card', ['book_id' => $edition->book->id]),
            'name' => $edition->book->title,
            'amount' => $orderProduct->amount,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::Buy,
            'message' => $this->prepareBody(OperationType::Buy, $params),
        ]);
    }

    /**
     * @param Order $order
     * @param mixed $orderProduct
     *
     * @return void
     * @throws ApplicationException
     */
    public function addReceivingSubscription(Order $order, mixed $orderProduct): void
    {
        $user = $order->user;
        $product = $orderProduct->orderable;
        $edition = $product::class::find($product->id);

        $params = [
            'url' => url('book-card', ['book_id' => $edition->book->id]),
            'name' => $edition->book->title,
            'amount' => $orderProduct->amount,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::Subscribed,
            'message' => $this->prepareBody(OperationType::Subscribed, $params),
        ]);
    }

    /**
     * @param Order $order
     * @param Profile $profile
     * @param int $donationAmount
     *
     * @return void
     * @throws ApplicationException
     */
    public function addMakingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void
    {
        $user = $order->user;

        $params = [
            'url' => url('author-page', ['profile_id' => $profile->id]),
            'name' => $profile->username,
            'amount' => $donationAmount,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::SupportMake,
            'message' => $this->prepareBody(OperationType::SupportMake, $params),
        ]);
    }

    /**
     * @param Order $order
     * @param Profile $profile
     * @param int $donationAmount
     *
     * @return void
     * @throws ApplicationException
     */
    public function addReceivingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void
    {
        $user = $order->user;

        $params = [
            'url' => url('author-page', ['profile_id' => $user->id]),
            'name' => $user->username,
            'amount' => $donationAmount,
        ];

        OperationHistory::create([
            'user_id' => $profile->user->id,
            'type' => OperationType::SupportReceive,
            'message' => $this->prepareBody(OperationType::SupportReceive, $params),
        ]);
    }

    public function addReceivingCertificatePublic(Order $order): void
    {
        // TODO: Implement addReceivingCertificatePublic() method.
    }

    /**
     * @param Order $order
     * @param AwardBook $awardBook
     *
     * @return void
     * @throws ApplicationException
     */
    public function addMakingAuthorReward(Order $order, AwardBook $awardBook): void
    {
        $user = $order->user;
        $award = $awardBook->award;
        $book = $awardBook->book;

        $params = [
            'url' => url('book-card', ['book_id' => $book->id]),
            'name' => $book->title,
            'amount' => $award->price,
        ];

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::RewardMake,
            'message' => $this->prepareBody(OperationType::RewardMake, $params),
        ]);
    }

    /**
     * @param OperationType $operationType
     * @param array $params
     *
     * @return string
     * @throws ApplicationException
     */
    protected function prepareBody(OperationType $operationType, array $params): string
    {
        $template = $operationType->bodyTemplate();
        $templatePath = plugins_path("books/profile/views/operations/{$template}.twig");

        if (! file_exists($templatePath) || ! is_readable($templatePath)) {
            throw new ApplicationException('Ошибка формирования записи в Истории Операций');
        }

        $markup = file_get_contents($templatePath);

        try {
            return (new Controller)
                ->getTwig()
                ->createTemplate($markup)
                ->render($params);
        } catch (Exception $e) {
            return Twig::parse($markup, $params);
        }
    }
}
