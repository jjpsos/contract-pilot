<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Invoice;

defined("ABSPATH") || exit();

/**
 * Application service for contract/invoice workflows.
 *
 * Holds the persistence/business orchestration that admin request handlers
 * previously performed inline. Methods receive already-sanitized data and
 * return a model instance on success or a WP_Error on failure. Request
 * concerns (nonces, capabilities, idempotency, flash messages, redirects)
 * remain in the admin layer.
 * For reads and listings, use contract_pilot()->invoices.
 */
class InvoiceService
{
    /**
     * Build, persist, and finalize an invoice from sanitized input.
     *
     * @param array $data Sanitized invoice fields. Recognized keys: id,
     *                     issue_date, due_date, contact_* fields,
     *                     order_number, discount_type, discount_value,
     *                     status, note, terms, items (array).
     *
     * @return Invoice|\WP_Error Saved invoice on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        $id = isset($data["id"]) ? absint($data["id"]) : 0;
        $items = isset($data["items"]) && is_array($data["items"])
            ? $data["items"]
            : [];

        $invoice = Invoice::make($id);
        $invoice->issue_date = isset($data["issue_date"]) ? $data["issue_date"] : "";
        $invoice->due_date = isset($data["due_date"]) ? $data["due_date"] : "";
        $invoice->contact_id = isset($data["contact_id"]) ? $data["contact_id"] : 0;
        $invoice->contact_name = isset($data["contact_name"]) ? $data["contact_name"] : "";
        $invoice->contact_company = isset($data["contact_company"]) ? $data["contact_company"] : "";
        $invoice->contact_email = isset($data["contact_email"]) ? $data["contact_email"] : "";
        $invoice->contact_phone = isset($data["contact_phone"]) ? $data["contact_phone"] : "";
        $invoice->contact_address = isset($data["contact_address"]) ? $data["contact_address"] : "";
        $invoice->contact_city = isset($data["contact_city"]) ? $data["contact_city"] : "";
        $invoice->contact_state = isset($data["contact_state"]) ? $data["contact_state"] : "";
        $invoice->contact_postcode = isset($data["contact_postcode"]) ? $data["contact_postcode"] : "";
        $invoice->contact_country = isset($data["contact_country"]) ? $data["contact_country"] : "";
        $invoice->contact_tax_number = isset($data["contact_tax_number"]) ? $data["contact_tax_number"] : "";
        $invoice->order_number = isset($data["order_number"]) ? $data["order_number"] : "";
        // Currency and exchange rate are fixed to base currency in the admin UI.
        $invoice->currency = contract_pilot_base_currency();
        $invoice->exchange_rate = 1;
        $invoice->discount_type = isset($data["discount_type"]) ? $data["discount_type"] : "fixed";
        $invoice->discount_value = isset($data["discount_value"]) ? $data["discount_value"] : 0;
        $invoice->status = isset($data["status"]) ? $data["status"] : "draft";
        $invoice->note = isset($data["note"]) ? $data["note"] : "";
        $invoice->terms = isset($data["terms"]) ? $data["terms"] : "";

        $invoice->items()->delete();
        $invoice->items = [];
        $invoice->set_items($items);
        $invoice->calculate_totals();

        $retval = $invoice->save();
        if (is_wp_error($retval)) {
            return $retval;
        }

        foreach ($invoice->items as $item) {
            $item->document_id = $invoice->id;
            $item->save();
            $taxes = $item->taxes;
            foreach ($taxes as $tax) {
                $tax->document_id = $invoice->id;
                $tax->document_item_id = $item->id;
                $tax->save();
            }
        }

        return $invoice;
    }

    /**
     * Transition an invoice to the "sent" status.
     *
     * @param Invoice $invoice Existing invoice.
     *
     * @return mixed Result of Invoice::save() (invoice on success, WP_Error on failure).
     */
    public function mark_sent($invoice)
    {
        $invoice->status = "sent";

        return $invoice->save();
    }

    /**
     * Transition an invoice to the "accept" status.
     *
     * @param Invoice $invoice Existing invoice.
     *
     * @return mixed Invoice/WP_Error from save(), or WP_Error when the current
     *               status does not allow acceptance.
     */
    public function mark_accept($invoice)
    {
        if (!in_array($invoice->status, ["sent", "overdue"], true)) {
            return new \WP_Error(
                "invalid_status",
                __(
                    "Only contracts that are sent or overdue can be marked as accepted.",
                    "contract-pilot",
                ),
            );
        }

        $invoice->status = "accept";

        return $invoice->save();
    }
}
