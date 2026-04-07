/**
 * Currency formatter utility
 * Formats prices according to store currency settings
 */

// Currency symbols mapping
export const CURRENCY_SYMBOLS: Record<string, string> = {
  INR: '₹',
  USD: '$',
  EUR: '€',
  GBP: '£',
  JPY: '¥',
  AUD: 'A$',
  CAD: 'C$',
  CHF: 'CHF',
  CNY: '¥',
  SEK: 'kr',
  NZD: 'NZ$',
  MXN: 'MX$',
  SGD: 'S$',
  HKD: 'HK$',
  NOK: 'kr',
  KRW: '₩',
  TRY: '₺',
  RUB: '₽',
  BRL: 'R$',
  ZAR: 'R',
  AED: 'د.إ',
  SAR: 'ر.س',
  THB: '฿',
  IDR: 'Rp',
  MYR: 'RM',
  PHP: '₱',
  VND: '₫',
  PKR: '₨',
  BDT: '৳',
  LKR: 'Rs',
};

// Currency names
export const CURRENCY_NAMES: Record<string, string> = {
  INR: 'Indian Rupee',
  USD: 'US Dollar',
  EUR: 'Euro',
  GBP: 'British Pound',
  JPY: 'Japanese Yen',
  AUD: 'Australian Dollar',
  CAD: 'Canadian Dollar',
  CHF: 'Swiss Franc',
  CNY: 'Chinese Yuan',
  SEK: 'Swedish Krona',
  NZD: 'New Zealand Dollar',
  MXN: 'Mexican Peso',
  SGD: 'Singapore Dollar',
  HKD: 'Hong Kong Dollar',
  NOK: 'Norwegian Krone',
  KRW: 'South Korean Won',
  TRY: 'Turkish Lira',
  RUB: 'Russian Ruble',
  BRL: 'Brazilian Real',
  ZAR: 'South African Rand',
  AED: 'UAE Dirham',
  SAR: 'Saudi Riyal',
  THB: 'Thai Baht',
  IDR: 'Indonesian Rupiah',
  MYR: 'Malaysian Ringgit',
  PHP: 'Philippine Peso',
  VND: 'Vietnamese Dong',
  PKR: 'Pakistani Rupee',
  BDT: 'Bangladeshi Taka',
  LKR: 'Sri Lankan Rupee',
};

/**
 * Get currency symbol for a currency code
 */
export const getCurrencySymbol = (currencyCode: string = 'INR'): string => {
  return CURRENCY_SYMBOLS[currencyCode.toUpperCase()] || currencyCode;
};

/**
 * Get currency name for a currency code
 */
export const getCurrencyName = (currencyCode: string = 'INR'): string => {
  return CURRENCY_NAMES[currencyCode.toUpperCase()] || currencyCode;
};

/**
 * Format price with currency symbol
 * @param amount - Price amount (can be string or number)
 * @param currencyCode - ISO currency code (default: INR)
 * @param showSymbol - Whether to show currency symbol (default: true)
 * @param decimals - Number of decimal places (default: 2)
 */
export const formatPrice = (
  amount: number | string | null | undefined,
  currencyCode: string = 'INR',
  showSymbol: boolean = true,
  decimals: number = 2
): string => {
  // Handle null/undefined
  if (amount === null || amount === undefined) {
    amount = 0;
  }

  // Convert to number if string
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;

  // Handle invalid numbers
  if (isNaN(numAmount)) {
    return showSymbol ? `${getCurrencySymbol(currencyCode)}0.00` : '0.00';
  }

  // Format the number
  const formatted = numAmount.toFixed(decimals);

  // Add thousand separators for INR (Indian numbering system)
  let parts = formatted.split('.');
  let integerPart = parts[0];
  const decimalPart = parts[1];

  if (currencyCode.toUpperCase() === 'INR') {
    // Indian numbering: 1,00,00,000 (groups of 2 after first 3 digits from right)
    const lastThree = integerPart.slice(-3);
    const otherNumbers = integerPart.slice(0, -3);
    if (otherNumbers !== '') {
      integerPart = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + lastThree;
    } else {
      integerPart = lastThree;
    }
  } else {
    // Western numbering: 10,000,000 (groups of 3)
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  const formattedNumber = decimalPart ? `${integerPart}.${decimalPart}` : integerPart;

  // Return with or without symbol
  if (showSymbol) {
    const symbol = getCurrencySymbol(currencyCode);
    // For INR, symbol comes before the number with space
    return currencyCode.toUpperCase() === 'INR'
      ? `${symbol}${formattedNumber}`
      : `${symbol}${formattedNumber}`;
  }

  return formattedNumber;
};

/**
 * Format price range
 */
export const formatPriceRange = (
  minAmount: number | string,
  maxAmount: number | string,
  currencyCode: string = 'INR'
): string => {
  const min = formatPrice(minAmount, currencyCode);
  const max = formatPrice(maxAmount, currencyCode);
  return `${min} - ${max}`;
};

/**
 * Parse formatted price string to number
 */
export const parsePrice = (priceString: string): number => {
  // Remove all non-numeric characters except decimal point
  const cleaned = priceString.replace(/[^\d.]/g, '');
  return parseFloat(cleaned) || 0;
};

/**
 * Get store currency from Redux state or localStorage
 */
export const getStoreCurrency = (): string => {
  // Try to get from localStorage first
  const storedCurrency = localStorage.getItem('store_currency');
  if (storedCurrency) {
    return storedCurrency;
  }
  
  // Default to INR
  return 'INR';
};

/**
 * Save store currency to localStorage
 */
export const saveStoreCurrency = (currencyCode: string): void => {
  localStorage.setItem('store_currency', currencyCode.toUpperCase());
};
