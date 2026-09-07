import { router, usePage } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, RotateCcwIcon } from "lucide-react";

type TransactionContainerProps = {
    transactions: any[];
    transactionsNextCursor: string | null;
};

export default function TransactionContainer({
    transactions,
    transactionsNextCursor,
}: TransactionContainerProps) {
    const { url } = usePage();

    const params = new URLSearchParams(url.split("?")[1] ?? "");
    const currentPage = Number(params.get("page")) || 1;

    const nextPage = () => {
        if (!transactionsNextCursor) {
            return;
        }

        router.get(
            "/dashboard",
            {
                prevId: transactionsNextCursor,
                page: currentPage + 1,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const prevPage = () => {
        if (currentPage <= 1) {
            return;
        }

        window.history.back();
    };

    return (
        <div className="col-span-2 mb-10 flex flex-col items-start gap-2 border border-green-900 p-4">
            <h2 className="flex items-center gap-2 text-xl font-medium">
                <RotateCcwIcon strokeWidth={1.5} />
                Transaction History
            </h2>

            {transactions.length === 0 ? (
                <div className="flex min-h-40 w-full items-center justify-center">
                    <p className="text-gray-500">No transactions yet.</p>
                </div>
            ) : (
                <>
                    <div className="mt-2 flex w-full flex-col gap-1">
                        <div className="flex flex-col gap-1 pr-1">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="border-b-2 border-green-700">
                                        <th className="py-2">Date</th>
                                        <th className="py-2">Type</th>
                                        <th className="py-2">Amount</th>
                                        <th className="py-2">USD Amount</th>
                                        <th className="py-2">Exchange Rate</th>
                                        <th className="py-2">Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {transactions.map((transaction) => {
                                        const btcAmount =
                                            Math.abs(
                                                Number(transaction.valueString),
                                            ) / 100_000_000;

                                        const usdRate = Number(
                                            transaction.usdRate,
                                        );

                                        const usdAmount = btcAmount * usdRate;

                                        return (
                                            <tr
                                                key={transaction.id}
                                                className="border-b border-green-900"
                                            >
                                                <td className="pb-1 pt-3">
                                                    {new Date(
                                                        transaction.createdTime,
                                                    ).toLocaleString()}
                                                </td>

                                                <td className="pt-3">
                                                    {transaction.type}
                                                </td>

                                                <td className="pt-3">
                                                    {btcAmount.toFixed(8)} TBTC
                                                </td>

                                                <td className="pt-3">
                                                    ${usdAmount.toFixed(2)}
                                                </td>

                                                <td className="pt-3">
                                                    ${usdRate.toFixed(2)}
                                                </td>

                                                <td className="pt-3">
                                                    {transaction.state}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex w-full items-center justify-between">
                        <button
                            type="button"
                            onClick={prevPage}
                            disabled={currentPage === 1}
                            className="flex cursor-pointer items-center gap-1 rounded-xs border px-3 py-1 transition-all duration-300 enabled:hover:border-green-400 enabled:hover:text-green-500 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            Previous
                        </button>

                        <p>Page {currentPage}</p>

                        <button
                            type="button"
                            onClick={nextPage}
                            disabled={!transactionsNextCursor}
                            className="flex cursor-pointer items-center gap-1 rounded-xs border px-3 py-1 transition-all duration-300 enabled:hover:border-green-400 enabled:hover:text-green-500 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Next
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}
