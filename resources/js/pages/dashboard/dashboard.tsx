import { Head, Link, router, usePage } from "@inertiajs/react";
import { LogOut, Wallet } from "lucide-react";

import { Button } from "@/components/ui/button";
import { logout } from "@/routes";

export default function Dashboard() {
    const { auth, bitcoinUsd, walletBalance } = usePage<{
        auth: any;
        bitcoinUsd: number;
        walletBalance: number;
    }>().props;

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
                    <div className="flex flex-col">
                        <p> 1 tbtc = ${bitcoinUsd.toFixed(2)} USD</p>
                        <p>
                            {" "}
                            Balance: {walletBalance.toFixed(8)} tbtc4,{" "}
                            {(walletBalance * bitcoinUsd).toFixed(2)} USD, Max
                            spendable amount: {walletBalance.toFixed(8)}{" "}
                            tbtc4{" "}
                        </p>
                    </div>

                    <div className="mt-10 w-full gap-4">
                        <div className="flex flex-col gap-2 border border-green-300 p-4">
                            <h2> Send</h2>
                            <div>
                                <h3>Recipient Address:</h3>
                                <input
                                    type="text"
                                    placeholder="Enter recipient address"
                                    className="w-full border rounded-lg p-2"
                                    cla
                                />
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
