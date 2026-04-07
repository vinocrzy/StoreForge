// Placeholder component - jvectormap has compatibility issues with Vite
// TODO: Replace with a Vite-compatible map library or charts

// Define the component props
interface CountryMapProps {
  mapColor?: string;
}

const CountryMap: React.FC<CountryMapProps> = ({ mapColor = "#465FFF" }) => {
  const countries = [
    { name: "United States", sales: 15420, percentage: 35 },
    { name: "India", sales: 8230, percentage: 19 },
    { name: "United Kingdom", sales: 6150, percentage: 14 },
    { name: "Australia", sales: 4890, percentage: 11 },
    { name: "Canada", sales: 3920, percentage: 9 },
    { name: "Others", sales: 5390, percentage: 12 },
  ];

  return (
    <div className="space-y-2">
      {countries.map((country, index) => (
        <div key={index} className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div 
              className="w-2 h-2 rounded-full" 
              style={{ backgroundColor: mapColor }}
            />
            <span className="text-sm text-gray-700 dark:text-gray-300">
              {country.name}
            </span>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm font-medium text-gray-900 dark:text-white">
              {country.sales.toLocaleString()}
            </span>
            <span className="text-xs text-gray-500 dark:text-gray-400 min-w-[40px] text-right">
              {country.percentage}%
            </span>
          </div>
        </div>
      ))}
    </div>
  );
};

export default CountryMap;
