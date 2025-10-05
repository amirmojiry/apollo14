import * as React from "react";
import { cn } from "../../lib/utils";

export function Button({ className, variant, ...props }) {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-all",
        "focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50",
        variant === "outline"
          ? "border border-primary bg-primary hover:bg-gray-100"
          : "bg-primary text-white hover:bg-primary/80",
        className
      )}
      {...props}
    />
  );
}
