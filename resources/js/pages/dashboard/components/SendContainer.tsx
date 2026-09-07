import InputError from "@/components/input-error";
import type { FormEvent } from "react";
import { useForm } from "@inertiajs/react";
import { BanknoteIcon, IdCardIcon, SendIcon } from "lucide-react";

type SendForm = {
    address: string;
    amount: string;
};

type WalletAddress = {
    address: string;
};

const MIN_SEND_AMOUNT = 0.00001;

function validateAmount(value: string, walletBalance: number): string {
    if (!value) {
        return "";
    }

    const amount = Number(value);

    if (!Number.isFinite(amount)) {
        return "Amount must be a valid number.";
    }

    if (amount < MIN_SEND_AMOUNT) {
        return `Minimum amount is ${MIN_SEND_AMOUNT} TBTC.`;
    }

    if (amount > walletBalance) {
        return `Amount cannot be greater than your balance of ${walletBalance} TBTC.`;
    }

    return "";
}

type SendContainerProps = {
    walletBalance: number;
    addresses: WalletAddress[];
};

export default function SendContainer({
    walletBalance,
    addresses,
}: SendContainerProps) {
    const { data, setData, post, processing, errors, reset } =
        useForm<SendForm>({
            address: "",
            amount: "",
        });

    const addressError =
        data.address &&
        addresses.some((address) => address.address === data.address)
            ? "Recipient address cannot be your own address."
            : "";
    const amountError = validateAmount(data.amount, walletBalance);
    const isDisabled =
        processing ||
        !data.address ||
        !data.amount ||
        !!amountError ||
        !!addressError;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isDisabled) {
            return;
        }

        post("/dashboard", {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="flex flex-col items-start gap-2 border border-green-900 p-4"
        >
            <h2 className="flex items-center gap-2 text-xl font-medium">
                <SendIcon strokeWidth={1.5} />
                Send
            </h2>

            <div className="mt-2 flex w-full flex-col gap-1">
                <label
                    htmlFor="recipient-address"
                    className="flex items-center gap-2"
                >
                    <IdCardIcon strokeWidth={1.5} />
                    Recipient Address:
                </label>

                <input
                    id="recipient-address"
                    type="text"
                    value={data.address}
                    onChange={(e) => setData("address", e.target.value)}
                    placeholder="Enter recipient address"
                    className="w-full rounded-lg border p-2"
                />

                <InputError message={addressError || errors.address} />
            </div>

            <div className="mt-2 flex w-full flex-col items-start gap-1">
                <label
                    htmlFor="send-amount"
                    className="flex items-center gap-2"
                >
                    <BanknoteIcon strokeWidth={1.5} />
                    Amount:
                </label>

                <input
                    id="send-amount"
                    type="number"
                    step={MIN_SEND_AMOUNT}
                    min={MIN_SEND_AMOUNT}
                    max={walletBalance}
                    value={data.amount}
                    onChange={(e) => setData("amount", e.target.value)}
                    placeholder="Enter amount in TBTC"
                    className="min-w-52 [appearance:textfield] rounded-lg border p-2 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                />

                <InputError message={amountError || errors.amount} />
            </div>

            <button
                type="submit"
                disabled={isDisabled}
                className="mt-4 cursor-pointer rounded-sm border-2 px-4 py-2 text-sm font-medium transition duration-300 hover:bg-green-500 hover:text-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing ? "Sending..." : "Send"}
            </button>
        </form>
    );
}
