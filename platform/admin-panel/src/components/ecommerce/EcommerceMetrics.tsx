import {
  ArrowDownIcon,
  ArrowUpIcon,
  BoxIconLine,
  GroupIcon,
} from "../../icons";
import Badge from "../ui/badge/Badge";
import type { DashboardStatistics } from "../../services/dashboard";

interface EcommerceMetricsProps {
  data: DashboardStatistics | undefined;
  isLoading: boolean;
}

export default function EcommerceMetrics({ data, isLoading }: EcommerceMetricsProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
        {[1, 2].map((i) => (
          <div key={i} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 animate-pulse">
            <div className="w-12 h-12 bg-gray-200 rounded-xl dark:bg-gray-700"></div>
            <div className="mt-5">
              <div className="h-4 bg-gray-200 rounded w-20 dark:bg-gray-700"></div>
              <div className="h-6 bg-gray-200 rounded w-24 mt-2 dark:bg-gray-700"></div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (!data) {
    return null;
  }

  const { customers, orders } = data;

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
      {/* <!-- Customers Metric --> */}
      <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div className="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
          <GroupIcon className="text-gray-800 size-6 dark:text-white/90" />
        </div>

        <div className="flex items-end justify-between mt-5">
          <div>
            <span className="text-sm text-gray-500 dark:text-gray-400">
              Customers
            </span>
            <h4 className="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
              {customers.total.toLocaleString()}
            </h4>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              +{customers.new_this_period} this period
            </p>
          </div>
          <Badge color={customers.trend === 'up' ? 'success' : 'error'}>
            {customers.trend === 'up' ? <ArrowUpIcon /> : <ArrowDownIcon />}
            {Math.abs(customers.change_percentage).toFixed(2)}%
          </Badge>
        </div>
      </div>

      {/* <!-- Orders Metric --> */}
      <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div className="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
          <BoxIconLine className="text-gray-800 size-6 dark:text-white/90" />
        </div>
        <div className="flex items-end justify-between mt-5">
          <div>
            <span className="text-sm text-gray-500 dark:text-gray-400">
              Orders
            </span>
            <h4 className="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
              {orders.total.toLocaleString()}
            </h4>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {orders.pending} pending · {orders.processing} processing
            </p>
          </div>

          <Badge color={orders.trend === 'up' ? 'success' : 'error'}>
            {orders.trend === 'up' ? <ArrowUpIcon /> : <ArrowDownIcon />}
            {Math.abs(orders.change_percentage).toFixed(2)}%
          </Badge>
        </div>
      </div>
    </div>
  );
}
