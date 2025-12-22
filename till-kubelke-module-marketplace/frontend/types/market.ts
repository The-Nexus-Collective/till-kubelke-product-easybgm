// ----------------------------------------------------------------------

export interface IMarket {
  code: string;
  name: string;
  localName: string | null;
  displayName: string;
  currency: string;
  defaultLocale: string;
  isActive: boolean;
  sortOrder: number;
}

export interface IMarketStats {
  total: number;
  active: number;
  inactive: number;
}

export interface IMarketInput {
  code: string;
  name: string;
  localName?: string;
  currency: string;
  defaultLocale: string;
  isActive?: boolean;
  sortOrder?: number;
}

