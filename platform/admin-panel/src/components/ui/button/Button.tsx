import { type ReactNode } from "react";

interface ButtonProps {
  children: ReactNode; // Button text or content
  size?: "sm" | "md" | "lg"; // Button size
  variant?: "primary" | "secondary" | "success" | "warning" | "danger" | "ghost" | "outline"; // Button variant
  startIcon?: ReactNode; // Icon before the text
  endIcon?: ReactNode; // Icon after the text
  onClick?: () => void; // Click handler
  disabled?: boolean; // Disabled state
  type?: "button" | "submit" | "reset"; // Button type
  className?: string; // Additional classes
}

const Button: React.FC<ButtonProps> = ({
  children,
  size = "md",
  variant = "primary",
  startIcon,
  endIcon,
  onClick,
  className = "",
  disabled = false,
  type = "button",
}) => {
  // Size Classes
  const sizeClasses = {
    sm: "px-3 py-2 text-sm",
    md: "px-5 py-3.5 text-sm",
    lg: "px-6 py-4 text-base",
  };

  // Variant Classes
  const variantClasses = {
    primary:
      "bg-primary text-white shadow-theme-xs hover:bg-primary/90 disabled:bg-primary/50 dark:bg-primary dark:hover:bg-primary/90",
    secondary:
      "bg-gray-600 text-white shadow-theme-xs hover:bg-gray-700 disabled:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 dark:disabled:bg-gray-800",
    success:
      "bg-success text-white shadow-theme-xs hover:bg-success/90 disabled:bg-success/50 dark:bg-success dark:hover:bg-success/90",
    warning:
      "bg-warning text-white shadow-theme-xs hover:bg-warning/90 disabled:bg-warning/50 dark:bg-warning dark:hover:bg-warning/90",
    danger:
      "bg-danger text-white shadow-theme-xs hover:bg-danger/90 disabled:bg-danger/50 dark:bg-danger dark:hover:bg-danger/90",
    ghost:
      "bg-transparent text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.08]",
    outline:
      "bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-boxdark dark:text-gray-300 dark:ring-strokedark dark:hover:bg-meta-4",
  };

  return (
    <button
      type={type}
      className={`inline-flex items-center justify-center gap-2 rounded-lg font-medium transition ${className} ${
        sizeClasses[size]
      } ${variantClasses[variant]} ${
        disabled ? "cursor-not-allowed opacity-50" : ""
      }`}
      onClick={onClick}
      disabled={disabled}
    >
      {startIcon && <span className="flex items-center">{startIcon}</span>}
      {children}
      {endIcon && <span className="flex items-center">{endIcon}</span>}
    </button>
  );
};

export default Button;
