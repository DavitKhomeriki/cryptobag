import { Wallet } from "lucide-react";
import type { ComponentProps } from "react";

import { cn } from "@/lib/utils";

export default function AppLogoIcon({ className, ...props }: ComponentProps<typeof Wallet>) {
    return <Wallet {...props} className={cn(className, "fill-none")} />;
}
