{{-- Shared plan band, rendered on ~60 audience and feature pages.

     Because it is shared, a wrong tier here is wrong sixty times: it used to bill
     "QR check-in" as the Pro column when TicketController::scan() has no plan check
     at all. The Pro half is the live check-in DASHBOARD (CheckInController). Selling
     tickets with a PRICE is Pro (Event::canSellPaidTickets); free registration, RSVP
     and $0 ticket rows are unlimited on every tier. Both columns say so now.

     $proMonthly / $entMonthly come from AppServiceProvider's marketing.* composer,
     via PlatformPricing, so an operator's own prices flow through. Never print a
     bare currency symbol here: plan_price() carries platform_currency(). --}}
<section class="relative bg-white py-20 dark:bg-[#0a0a0f] lg:py-24">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto mb-10 max-w-2xl text-center">
            <h2 class="es-balance text-2xl font-black tracking-tight text-gray-900 dark:text-white md:text-3xl" data-reveal>
                100% Free Access. No Subscriptions.
            </h2>
            <p class="mt-3 text-gray-600 dark:text-gray-400" data-reveal style="--reveal-delay: 0.08s;">
                All premium features, custom branding, seating charts, and integrations are included free for everyone. We only earn a small percentage fee when you sell paid tickets.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 max-w-3xl mx-auto" data-reveal-group="90">
            <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-white/[0.03]" data-reveal="panel">
                <div class="mb-3 flex items-baseline gap-2">
                    <span class="text-sm font-bold uppercase tracking-[0.14em] text-blue-600 dark:text-blue-400">Free Events</span>
                </div>
                <div class="mb-1 flex items-baseline gap-1">
                    <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">$0</span>
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">forever</span>
                </div>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">Host unlimited free events and registrations without paying a single dime.</p>
                <ul class="mb-6 space-y-3">
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Unlimited events, calendars, & sub-schedules</span>
                    </li>
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Google, Outlook, & CalDAV 2-way sync</span>
                    </li>
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>All Pro & Enterprise features unlocked</span>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col rounded-2xl border border-blue-300 bg-blue-50/40 p-8 dark:border-blue-500/40 dark:bg-blue-500/[0.07]" data-reveal="panel">
                <div class="mb-3 flex items-baseline gap-2">
                    <span class="text-sm font-bold uppercase tracking-[0.14em] text-blue-600 dark:text-blue-400">Paid Ticket Sales</span>
                </div>
                <div class="mb-1 flex items-baseline gap-1">
                    <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">5%</span>
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">per ticket sold</span>
                </div>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">Only pay when you earn money. Automatically deducted at ticket checkout.</p>
                <ul class="mb-6 space-y-3">
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Direct payouts to your Stripe account</span>
                    </li>
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Passes, gift cards, promo codes, & installments</span>
                    </li>
                    <li class="flex gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>No monthly or hidden subscription fees</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-center gap-5 sm:flex-row sm:gap-7" data-reveal>
            <a href="{{ app_url('/sign_up') }}" class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-sky-600 px-8 py-4 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:shadow-2xl hover:shadow-blue-500/40">
                Get Started Now
                <svg aria-hidden="true" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </div>
</section>
