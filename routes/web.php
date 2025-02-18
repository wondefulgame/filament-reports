<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::name('filament.')
    ->group(function () {
        foreach (Filament::getPanels() as $panel) {
            /** @var \Filament\Panel $panel */
            $panelId = $panel->getId();
            $hasTenancy = $panel->hasTenancy();
            $tenantDomain = $panel->getTenantDomain();
            $tenantRoutePrefix = $panel->getTenantRoutePrefix();
            $tenantSlugAttribute = $panel->getTenantSlugAttribute();
            $domains = $panel->getDomains();

            foreach ((empty($domains) ? [null] : $domains) as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name("{$panelId}.")
                    ->prefix($panel->getPath().'/reports')
                    ->group(function () use ($panel, $hasTenancy, $tenantDomain, $tenantRoutePrefix, $tenantSlugAttribute) {

                        Route::middleware($panel->getAuthMiddleware())
                            ->group(function () use ($panel, $hasTenancy, $tenantDomain, $tenantRoutePrefix, $tenantSlugAttribute): void {
                                $routeGroup = Route::middleware($hasTenancy ? $panel->getTenantMiddleware() : []);

                                if (filled($tenantDomain)) {
                                    $routeGroup->domain($tenantDomain);
                                } else {
                                    $routeGroup->prefix(
                                        ($hasTenancy && blank($tenantDomain)) ?
                                            (
                                                filled($tenantRoutePrefix) ?
                                                    "{$tenantRoutePrefix}/" :
                                                    ''
                                            ).('{tenant'.(
                                                filled($tenantSlugAttribute) ?
                                                    ":{$tenantSlugAttribute}" :
                                                    ''
                                            ).'}') :
                                            '',
                                    );
                                }

                                $routeGroup
                                    ->group(function () use ($panel): void {
                                        Route::name('reports.')->group(function () use ($panel): void {
                                            foreach (reports()->getReports() as $report) {
                                                $report::routes($panel);
                                            }
                                        });
                                    });

                            });
                    });
            }
        }
    });
