import { Link, router } from "@inertiajs/react";
import { Wallet, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { logout } from "@/routes";
import { User } from "@/types";

type HeaderProps = {
    auth: {
        user: User;
    };
};

export default function Header({ auth }: HeaderProps) {
    return (
        <header className="flex flex-col">
            <div className="flex justify-between px-10 ">
                <div className="flex items-center gap-2 text-xl font-semibold">
                    <Wallet className="size-6" />
                    <h1>{auth.user.username}'s Wallet</h1>
                </div>
                <Button asChild variant="outline">
                    <Link
                        href={logout()}
                        as="button"
                        onClick={() => router.flushAll()}
                        data-test="logout-button"
                    >
                        <LogOut />
                        Log out
                    </Link>
                </Button>
            </div>
            <hr className="mt-7 border-green-900" />
        </header>
    );
}
