import { Head, Link, router, usePage } from "@inertiajs/react";
import { LogOut, Wallet } from "lucide-react";

import { Button } from "@/components/ui/button";
import { logout } from "@/routes";

export default function Dashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col bg-background text-foreground min-h-svh pt-10">
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
                    <hr className="mt-7 border-green-300" />
                </header>

                <main className="mx-auto mt-12 w-full px-10">
                    
                </main>
            </div>
        </>
    );
}
