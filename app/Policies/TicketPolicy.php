<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (! $user->can('ticket.view')) {
            return false;
        }

        if ($user->can('ticket.view_all')) {
            return true;
        }

        if ($user->hasRole('pic')) {
            return true;
        }

        if ($ticket->created_by === $user->id && ! $this->isResponderOnlyUser($user)) {
            return true;
        }

        $assigned = $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (! $assigned) {
            return false;
        }

        if ($this->isResponderOnlyUser($user)) {
            return $this->ticketPassesResponderAssignmentViewGate($ticket);
        }

        return true;
    }

    /**
     * Akses halaman penanganan (responder): tiket sudah dianalisis, sub-status sesuai, dan user lolos aturan view.
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        if (! $user->can('ticket.respond')) {
            return false;
        }

        if ($ticket->isTerminal() || $ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        if (! in_array($ticket->sub_status, [
            Ticket::SUB_STATUS_ANALYSIS,
            Ticket::SUB_STATUS_RESPONSE,
            Ticket::SUB_STATUS_RESOLUTION,
        ], true)) {
            return false;
        }

        return $this->view($user, $ticket);
    }

    /**
     * Memulai fase Response dari Analysis (tombol "Tangani Tiket" di halaman responder).
     */
    public function beginResponseHandling(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal() || $ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if ($ticket->report_status !== Ticket::REPORT_STATUS_VERIFIED) {
            return false;
        }

        if (! $user->can('ticket.respond')) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        if ($ticket->sub_status !== Ticket::SUB_STATUS_ANALYSIS) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Menandai penanganan respons selesai (promosi ke Resolution).
     */
    public function markResponseResolved(User $user, Ticket $ticket): bool
    {
        if ($ticket->sub_status !== Ticket::SUB_STATUS_RESPONSE) {
            return false;
        }

        if (! $ticket->responseActions()->exists()) {
            return false;
        }

        if ($ticket->isTerminal() || $ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $user->can('ticket.respond')) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    private function isResponderOnlyUser(User $user): bool
    {
        return $user->can('ticket.respond')
            && ! $user->can('ticket.analyze')
            && ! $user->hasRole('pic')
            && ! $user->can('ticket.view_all');
    }

    /**
     * Responder murni: hanya tiket yang relevan untuk alur respons (setelah analisis, sub-status tidak lagi Triage).
     */
    private function ticketPassesResponderAssignmentViewGate(Ticket $ticket): bool
    {
        if (! $ticket->analyses()->exists()) {
            return false;
        }

        return in_array($ticket->sub_status, [
            Ticket::SUB_STATUS_ANALYSIS,
            Ticket::SUB_STATUS_RESPONSE,
            Ticket::SUB_STATUS_RESOLUTION,
        ], true);
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $user->can('ticket.update_status')) {
            return false;
        }

        if (! $user->can('ticket.analyze') && ! $user->can('ticket.respond')) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Mencatat analisis insiden + IOC: pengguna ter-assign (primary/contributor) dengan ticket.analyze.
     */
    public function analyze(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $user->can('ticket.analyze')) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Mencatat tindakan respons (mitigasi / eradikasi / recovery): hanya responder ter-assign aktif.
     */
    public function recordResponseAction(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $user->can('ticket.respond')) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        if ($ticket->sub_status !== Ticket::SUB_STATUS_RESPONSE) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Koordinator membuka kembali fase Response dari Resolution agar responder dapat mencatat tindakan tambahan.
     */
    public function reopenResponseRecording(User $user, Ticket $ticket): bool
    {
        if (! $user->can('ticket.reopen_closed')) {
            return false;
        }

        if ($ticket->isTerminal() || $ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if ($ticket->report_status !== Ticket::REPORT_STATUS_VERIFIED) {
            return false;
        }

        if ($ticket->sub_status !== Ticket::SUB_STATUS_RESOLUTION) {
            return false;
        }

        return $ticket->analyses()->exists();
    }

    /**
     * Koordinator: menandai penanganan respons selesai (Validated).
     */
    public function validateHandling(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if ($ticket->sub_status !== Ticket::SUB_STATUS_RESOLUTION) {
            return false;
        }

        if ($ticket->report_status !== Ticket::REPORT_STATUS_VERIFIED) {
            return false;
        }

        if (! $ticket->report_is_valid) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        if (! $ticket->responseActions()->exists()) {
            return false;
        }

        return $user->can('ticket.validate_handling');
    }

    /**
     * Koordinator: membuka kembali tiket dari Closed untuk fase Response.
     */
    public function reopenClosed(User $user, Ticket $ticket): bool
    {
        if (! $user->can('ticket.reopen_closed')) {
            return false;
        }

        if (! $ticket->isClosed()) {
            return false;
        }

        if ($ticket->isReportRejected()) {
            return false;
        }

        if ($ticket->report_status !== Ticket::REPORT_STATUS_VERIFIED) {
            return false;
        }

        if (! $ticket->report_is_valid) {
            return false;
        }

        return $ticket->analyses()->exists();
    }

    /**
     * Koordinator: mengelola laporan koordinator untuk tiket.
     */
    public function manageIncidentReport(User $user, Ticket $ticket): bool
    {
        if ($ticket->isReportRejected()) {
            return false;
        }

        return $user->can('ticket.incident_report.manage');
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        if ($ticket->isReportRejected()) {
            return false;
        }

        return $user->can('ticket.close');
    }

    public function verifyReport(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if (! $user->hasRole('pic')) {
            return false;
        }

        return $ticket->report_status === Ticket::REPORT_STATUS_PENDING
            && $ticket->status === Ticket::STATUS_AWAITING_VERIFICATION;
    }

    public function rejectReport(User $user, Ticket $ticket): bool
    {
        return $this->verifyReport($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        // Koordinator bisa mengassign langsung selama tiket tidak terminal.
        if ($user->can('ticket.assign') && $user->can('ticket.close')) {
            return true;
        }

        if ($user->hasRole('pic')) {
            return $ticket->report_status === Ticket::REPORT_STATUS_VERIFIED
                && $ticket->status === Ticket::STATUS_OPEN;
        }

        // Analis/responder ter-assign tidak memegang hak assign; penugasan ulang lewat koordinator / alur PIC saat Open.
        return false;
    }

    /**
     * Handoff utama ke responder setelah ada analisis (PIC / koordinator), saat tiket masih On Progress.
     */
    public function assignResponderHandoff(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if ($ticket->report_status !== Ticket::REPORT_STATUS_VERIFIED) {
            return false;
        }

        if (! $ticket->analyses()->exists()) {
            return false;
        }

        if (! in_array($ticket->sub_status, [
            Ticket::SUB_STATUS_ANALYSIS,
            Ticket::SUB_STATUS_RESPONSE,
            Ticket::SUB_STATUS_RESOLUTION,
        ], true)) {
            return false;
        }

        // Koordinator bisa meng-handoff langsung selama tiket belum terminal.
        if ($user->can('ticket.assign') && $user->can('ticket.close')) {
            return true;
        }

        return $user->hasRole('pic');
    }

    /**
     * PIC dapat memverifikasi laporan (Pending) atau menugaskan setelah Verified + Open.
     */
    public function interactAsPic(User $user, Ticket $ticket): bool
    {
        if (! $user->hasRole('pic')) {
            return false;
        }

        return $this->verifyReport($user, $ticket)
            || $this->assign($user, $ticket)
            || $this->assignResponderHandoff($user, $ticket);
    }
}
