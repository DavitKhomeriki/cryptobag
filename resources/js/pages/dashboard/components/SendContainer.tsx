import { useForm } from "@inertiajs/react";
import { BanknoteIcon, IdCardIcon, SendIcon } from "lucide-react";

type SendForm = {
    address: string;
    amount: string;
};

type SendContainerProps = {
    walletBalance: number;
};

export default function SendContainer({ walletBalance }: SendContainerProps) {
    const { data, setData, post, processing, errors, reset } =
        useForm<SendForm>({
            address: "",
            amount: "",
        });

    const amount = Number(data.amount);

    const amountError = (() => {
        if (!data.amount) {
            return "";
        }

        if (Number.isNaN(amount)) {
            return "Amount must be a valid number.";
        }

        if (amount < 0.00001) {
            return "Minimum amount is 0.00001 TBTC.";
        }

        if (amount > walletBalance) {
            return `Amount cannot be greater than your balance of ${walletBalance} TBTC.`;
        }

        return "";
    })();

    const send = () => {
        if (amountError) return;

        post("/dashboard", {
            preserveScroll: true,

            onSuccess: () => {
                reset();
            },
        });
    };

    const isDisabled =
        processing || !data.address || !data.amount || !!amountError;

    return (
        <div className="flex flex-col items-start gap-2 border border-green-900 p-4">
            <h2 className="flex items-center gap-2 text-xl font-medium">
                <SendIcon strokeWidth={1.5} />
                Send
            </h2>

            <div className="mt-2 flex w-full flex-col gap-1">
                <h3 className="flex items-center gap-2">
                    <IdCardIcon strokeWidth={1.5} />
                    Recipient Address:
                </h3>

                <input
                    type="text"
                    value={data.address}
                    onChange={(e) => setData("address", e.target.value)}
                    placeholder="Enter recipient address"
                    className="w-full rounded-lg border p-2"
                />

                {errors.address && (
                    <p className="text-sm text-red-500">{errors.address}</p>
                )}
            </div>

            <div className="mt-2 flex w-full flex-col items-start gap-1">
                <h3 className="flex items-center gap-2">
                    <BanknoteIcon strokeWidth={1.5} />
                    Amount:
                </h3>

                <input
                    type="number"
                    step="0.00001"
                    min="0.00001"
                    max={walletBalance}
                    value={data.amount}
                    onChange={(e) => setData("amount", e.target.value)}
                    placeholder="Enter amount in TBTC"
                    className="
                        min-w-52 rounded-lg border p-2
                        [appearance:textfield]
                        [&::-webkit-inner-spin-button]:appearance-none
                        [&::-webkit-outer-spin-button]:appearance-none
                    "
                />

                {amountError && (
                    <p className="text-sm text-red-500">{amountError}</p>
                )}

                {errors.amount && (
                    <p className="text-sm text-red-500">{errors.amount}</p>
                )}
            </div>

            <button
                type="button"
                onClick={send}
                disabled={isDisabled}
                className="mt-4 cursor-pointer rounded-sm border-2 px-4 py-2 text-sm font-medium transition duration-300 hover:bg-green-500 hover:text-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing ? "Sending..." : "Send"}
            </button>
        </div>
    );
}
