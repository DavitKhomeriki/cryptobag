import { Head, router, usePage } from "@inertiajs/react";
import Header from "@/pages/dashboard/components/Header";
import Balance from "@/pages/dashboard/components/Balance";
import SendContainer from "@/pages/dashboard/components/SendContainer";
import ReceiveContainer from "@/pages/dashboard/components/ReceiveContainer";
import TransactionContainer from "./components/TransactionContainer";
import { useEffect } from "react";
import echo from "@/echo";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { useClipboard } from "@/hooks/use-clipboard";

export default function Dashboard() {
    const [copiedText, copy] = useClipboard();
    const {
        auth,
        walletId,
        passphrase,
        bitcoinUsd,
        walletBalance,
        addresses,
        transactions,
        transactionsNextCursor,
        merchantApiToken,
    } = usePage<{
        auth: any;
        walletId: number;
        passphrase: string;
        bitcoinUsd: number;
        walletBalance: number;
        addresses: any;
        transactions: any;
        transactionsNextCursor: string | null;
        merchantApiToken?: string | null;
    }>().props;

    useEffect(() => {
        if (!echo) {
            return;
        }

        const channelName = `wallet.${auth.user.id}`;
        const channel = echo.private(channelName);

        const refreshWallet = () => {
            router.reload({
                only: [
                    "walletBalance",
                    "transactions",
                    "transactionsNextCursor",
                ],
            });
        };

        channel.listen(".wallet.updated", refreshWallet);

        return () => {
            echo?.leave(channelName);
        };
    }, [auth.user.id]);

    return (
        <>
            <Head title="Dashboard" />
            <div className="bg-background text-foreground flex min-h-svh flex-col pt-10">
                <Header auth={auth} />

                <main className="mx-auto mt-12 w-full px-10">
                    {merchantApiToken && (
                        <Alert className="mb-8">
                            <AlertTitle>Merchant API token</AlertTitle>
                            <AlertDescription>
                                <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center">
                                    <code className="border-input bg-muted text-foreground min-w-0 flex-1 rounded-md border px-3 py-2 text-xs break-all">
                                        {merchantApiToken}
                                    </code>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => copy(merchantApiToken)}
                                    >
                                        {copiedText === merchantApiToken
                                            ? "Copied"
                                            : "Copy"}
                                    </Button>
                                </div>
                            </AlertDescription>
                        </Alert>
                    )}

                    <Balance
                        walletId={walletId}
                        passphrase={passphrase}
                        bitcoinUsd={bitcoinUsd}
                        walletBalance={walletBalance}
                    />

                    <div className="mt-10 grid w-full grid-cols-2 gap-7">
                        <SendContainer
                            walletBalance={walletBalance}
                            addresses={addresses.addresses}
                        />
                        <ReceiveContainer addresses={addresses.addresses} />
                        <TransactionContainer
                            transactions={transactions}
                            transactionsNextCursor={transactionsNextCursor}
                        />
                    </div>
                </main>
            </div>
        </>
    );
}
