/*
| Copy-to-clipboard with a toast (22.5).
|
| Guests copy an account number on a phone, at a wedding, in a hurry. The
| confirmation has to be immediate and unmissable, and the fallback has to
| work: `navigator.clipboard` needs a secure context, which a guest on a
| shared hotspot behind a plain-HTTP proxy may not have.
*/
import { ref } from 'vue';

export function useClipboard(timeout = 2000) {
    const toast = ref(null);

    let timer = null;

    const show = (message) => {
        toast.value = message;

        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            toast.value = null;
        }, timeout);
    };

    /**
     * The old textarea trick, for every context the Clipboard API refuses.
     */
    function copyBySelection(value) {
        try {
            const field = document.createElement('textarea');
            field.value = value;
            field.setAttribute('readonly', '');
            field.style.position = 'absolute';
            field.style.left = '-9999px';
            document.body.appendChild(field);
            field.select();

            const copied = document.execCommand('copy');
            document.body.removeChild(field);

            return copied;
        } catch {
            return false;
        }
    }

    async function copy(value, message = 'Berhasil disalin') {
        if (!value) {
            return false;
        }

        /*
        | Try the Clipboard API, then fall through — not into an error. It
        | rejects for reasons that have nothing to do with the guest: an
        | unfocused document, a WhatsApp in-app webview, a page served over
        | plain HTTP behind a hotspot's proxy. Treating a rejection as failure
        | means the button does nothing on exactly the browsers this product
        | is opened in most.
        */
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(value);
                show(message);

                return true;
            } catch {
                // Fall through to the selection fallback below.
            }
        }

        if (copyBySelection(value)) {
            show(message);

            return true;
        }

        show('Gagal menyalin. Salin manual, ya.');

        return false;
    }

    return { toast, copy };
}
