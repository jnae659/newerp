<?php

namespace App\Models\Traits;

use App\Models\Utility;

trait HasFormatting
{
    public function priceFormat($price)
    {
        $number = explode('.', $price);
        $length = strlen(trim($number[0]));
        $float_number = Utility::getValByName('float_number') == 'dot' ? '.' : ',';

        if ($length > 3) {
            $decimal_separator = Utility::getValByName('decimal_separator') == 'dot' ? ',' : ',';
            $thousand_separator = Utility::getValByName('thousand_separator') == 'dot' ? '.' : ',';
        } else {
            $decimal_separator = Utility::getValByName('decimal_separator') == 'dot' ? '.' : ',';
            $thousand_separator = Utility::getValByName('thousand_separator') == 'dot' ? '.' : ',';
        }

        $currency = Utility::getValByName('currency_symbol') == 'withcurrencysymbol' ? Utility::getValByName('site_currency_symbol') : Utility::getValByName('site_currency');
        $settings = Utility::settings();
        $decimal_number = Utility::getValByName('decimal_number') ? Utility::getValByName('decimal_number') : 0;
        $currency_space = Utility::getValByName('currency_space');
        $price = number_format($price, $decimal_number, $decimal_separator, $thousand_separator);

        if ($float_number == 'dot') {
            $price = preg_replace('/' . preg_quote($thousand_separator, '/') . '([^' . preg_quote($thousand_separator, '/') . ']*)$/', $float_number . '$1', $price);
        } else {
            $price = preg_replace('/' . preg_quote($decimal_separator, '/') . '([^' . preg_quote($decimal_separator, '/') . ']*)$/', $float_number . '$1', $price);
        }

        return (($settings['site_currency_symbol_position'] == "pre") ? $currency : '') . ($currency_space == 'withspace' ? ' ' : '') . $price . ($currency_space == 'withspace' ? ' ' : '') . (($settings['site_currency_symbol_position'] == "post") ? $currency : '');
    }

    public function currencySymbol()
    {
        $settings = Utility::settings();
        return $settings['site_currency_symbol'];
    }

    public function dateFormat($date)
    {
        $settings = Utility::settings();
        return date($settings['site_date_format'], strtotime($date));
    }

    public function timeFormat($time)
    {
        $settings = Utility::settings();
        return date($settings['site_time_format'], strtotime($time));
    }

    public function DateTimeFormat($date)
    {
        $settings = Utility::settings();
        $date_formate = !empty($settings['site_date_format']) ? $settings['site_date_format'] : 'd-m-y';
        $time_formate = !empty($settings['site_time_format']) ? $settings['site_time_format'] : 'H:i';
        return date($date_formate . ' ' . $time_formate, strtotime($date));
    }

    public function purchaseNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["purchase_prefix"] . sprintf("%05d", $number);
    }

    public static function quotationNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["quotation_prefix"] . sprintf("%05d", $number);
    }

    public function posNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["pos_prefix"] . sprintf("%05d", $number);
    }

    public function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public function proposalNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["proposal_prefix"] . sprintf("%05d", $number);
    }

    public function contractNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["contract_prefix"] . sprintf("%05d", $number);
    }

    public function billNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["bill_prefix"] . sprintf("%05d", $number);
    }

    public function expenseNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["expense_prefix"] . sprintf("%05d", $number);
    }

    public function journalNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["journal_prefix"] . sprintf("%05d", $number);
    }

    public function customerNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["customer_prefix"] . sprintf("%05d", $number);
    }

    public function venderNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["vender_prefix"] . sprintf("%05d", $number);
    }

    public function bugNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["bug_prefix"] . sprintf("%05d", $number);
    }
}
