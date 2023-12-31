<?php

namespace Books\Profile\Contracts;

use Books\Book\Models\AwardBook;
use Books\Book\Models\Edition;
use Books\Orders\Models\Order;
use Books\Profile\Models\Profile;

interface OperationHistoryService
{
    /** Пополнение баланса */
    public function addBalanceDeposit(Order $order): void;

    /** Получение сертификата анонимно */
    public function addReceivingCertificateAnonymous(Order $order): void;

    /** Вывод средств */
    public function addWithdrawal(Order $order): void;

    /** Покупка книги (оплата) */
    public function addReceivingPurchase(Order $order, Edition $edition): void;

    /** Покупка подписки на книгу(оплата) */
    public function addReceivingSubscription(Order $order, Edition $edition): void;

    /** Поддержка автора (оплата) */
    public function addMakingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void;

    /** Поддержка автора (получение) */
    public function addReceivingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void;

    /** Получение сертификата не анонимно */
    public function addReceivingCertificatePublic(Order $order): void;

    /** Покупка награды (оплата) */
    public function addMakingAuthorReward(Order $order, AwardBook $awardBook): void;
}
