<?php

namespace Cryptixcoder\Marketplace;

use Log;

use Stripe\Stripe;
use Stripe\Account as StripeAccount;
use Stripe\Charge as StripeCharge;
use Stripe\Transfer as StripeTransfer;

use Stripe\Error\Card as StripeCardError;
use Stripe\Error\ApiConnection as StripeApiConnectionError;
use Stripe\Error\Base as StripeBaseError;

Stripe::setApiKey(config('services.stripe.key'));

trait Marketplace{

	public function newAccount($stripeToken, $data = array()){
		$opts = [
			'country' => 'US',
			'managed' => true,
			'legal_entity' => [
				'type' => 'individual',
				'first_name' => 'Jane',
				'last_name' =. 'Doe',
				'ssn_last_4' => '',
				'address' => [
					'city' => '',
					'state' => '',
					'line1' => '',
					'line2' => '',
					'country' => '',
					'postal_code' => ''
				],
				'dob' => [
					'day' => '',
					'month' => '',
					'year' => ''
				]
			],
			'tos_acceptance' => [
				'date' => time(),
				'ip' => $_SERVER['REMOTE_ADDR']
			]
		];

		$payload = array_merge($opts, $data);

		$account = StripeAccount::create($payload);

		$this->stripe_account_id = $account->id;

		$this->save();

		return $account->id;
	}

	public function pay($amount, $token, $data = array() ){

		$opts = [
			'amount' => ($amount * 100),
			'currency' => 'usd',
			'source' => $token,
			'destination' => $this->stripe_account_id
		];

		$payload = array_merge($opts, $data);

		try{
			$charge = StripeCharge::create($payloads);
			return $charge;
		}
		catch(StripeCardError $e){
			Log::info('Stripe card error: ' . $e->getMessage());
		}
		catch(Exception $e){
			Log::info('Stripe Error: ' . $e->getMessage());
		}

		return false;
	}

	public function updateBankAccount($token, $data = array()){
		$account = StripeAccount::retrieve($this->stripe_account_id);

		$account->bank_account = $token;

		if(array_key_exists('transfer_schedule', $data)){
			$account->transfer_schedule->interval = $data['interval'];
		}

		$account->save();
	}

	public function sendFromPlatform($amount, $data = array()){
		$payout = $amount * 100;

		$opts = [
			'amount' = >$payout,
			'currency' => 'usd',
			'destination' => $this->stripe_account_id,
		];

		$payload = array_merge($opts, $data);

		$transfer = StripeTransfer::create($payload);

		return true;
	}
}