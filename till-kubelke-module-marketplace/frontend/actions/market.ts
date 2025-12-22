import type { IMarket, IMarketStats } from "src/types/market";

import useSWR from "swr";

import { fetcher, endpoints } from "src/lib/axios";

// ----------------------------------------------------------------------

/**
 * Get all markets (admin view, includes inactive)
 */
export function useGetMarkets(includeInactive = true) {
  const url = `${endpoints.system.markets.list}?includeInactive=${includeInactive}`;

  const { data, error, isLoading, mutate } = useSWR<{
    markets: IMarket[];
    total: number;
  }>(url, fetcher);

  return {
    markets: data?.markets || [],
    total: data?.total || 0,
    marketsLoading: isLoading,
    marketsError: error,
    marketsEmpty: !isLoading && !data?.markets?.length,
    marketsMutate: mutate,
  };
}

/**
 * Get a single market by code
 */
export function useGetMarket(code: string | null) {
  const url = code ? endpoints.system.markets.get(code) : null;

  const { data, error, isLoading, mutate } = useSWR<IMarket>(url, fetcher);

  return {
    market: data,
    marketLoading: isLoading,
    marketError: error,
    marketMutate: mutate,
  };
}

/**
 * Get market statistics
 */
export function useGetMarketStats() {
  const { data, error, isLoading, mutate } = useSWR<IMarketStats>(
    endpoints.system.markets.stats,
    fetcher,
  );

  return {
    stats: data || { total: 0, active: 0, inactive: 0 },
    statsLoading: isLoading,
    statsError: error,
    statsMutate: mutate,
  };
}

/**
 * Get public markets (for dropdowns, only active)
 */
export function useGetPublicMarkets() {
  const { data, error, isLoading } = useSWR<{ markets: IMarket[] }>(
    endpoints.system.markets.public,
    fetcher,
  );

  return {
    markets: data?.markets || [],
    marketsLoading: isLoading,
    marketsError: error,
  };
}

