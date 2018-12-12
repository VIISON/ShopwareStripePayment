{* An initially hidden CVC info popup window *}
{strip}
    <div class="stripe-payment-card-cvc-info-wrapper">
        <div class="stripe-payment-visa stripe-payment-card-cvc-info-popup-cardtype">
            <div class="stripe-payment-card-cvc-header">
                <span class="stripe-payment-card-cvc-header-title">
                    {s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/visa_mastercard/header/title/card}{/s}<br>
                    <strong>{s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/visa_mastercard/header/title/front}{/s}</strong>
                </span>
                <div class="stripe-payment-card-cvc-header-logos">
                    <div class="stripe-payment-provider-logo mastercard"></div>
                    <div class="stripe-payment-provider-logo visa"></div>
                </div>
            </div>
            <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNy4wLjIsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB3aWR0aD0iMzEwcHgiIGhlaWdodD0iMTgwcHgiIHZpZXdCb3g9IjAgMCAzMTAgMTgwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAzMTAgMTgwIiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxnPg0KCQk8cGF0aCBmaWxsPSIjRjBGMEYwIiBkPSJNMzAxLjUsMTY1LjZjMCw3Ljk1My02LjcxNSwxNC40LTE1LDE0LjRoLTI3MGMtOC4yODQsMC0xNS02LjQ0Ny0xNS0xNC40VjE0LjRDMS41LDYuNDQ3LDguMjE2LDAsMTYuNSwwDQoJCQloMjcwYzguMjg1LDAsMTUsNi40NDcsMTUsMTQuNFYxNjUuNnoiLz4NCgk8L2c+DQoJPHJlY3QgeD0iMS41IiB5PSIzNSIgZmlsbD0iIzU1NTU1NSIgd2lkdGg9IjMwMCIgaGVpZ2h0PSI0MCIvPg0KCTxyZWN0IHg9IjEuNSIgeT0iNzUiIGZpbGw9IiNGRkZGRkYiIHdpZHRoPSIzMDAiIGhlaWdodD0iNDAiLz4NCgk8Zz4NCgkJPHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTI3NC45MjcsMTI3LjA3M2MtMTcuNjg2LDAtMzIuMDczLTE0LjM4OS0zMi4wNzMtMzIuMDc0YzAtMTcuNjg1LDE0LjM4OC0zMi4wNzIsMzIuMDczLTMyLjA3Mg0KCQkJUzMwNyw3Ny4zMTQsMzA3LDk0Ljk5OUMzMDcsMTEyLjY4NSwyOTIuNjEyLDEyNy4wNzMsMjc0LjkyNywxMjcuMDczeiIvPg0KCQk8cGF0aCBmaWxsPSIjMzMzMzMzIiBkPSJNMjc0LjkyNyw2NC40MjdjMTYuODU4LDAsMzAuNTczLDEzLjcxNSwzMC41NzMsMzAuNTcyYzAsMTYuODU4LTEzLjcxNSwzMC41NzMtMzAuNTczLDMwLjU3Mw0KCQkJYy0xNi44NTgsMC0zMC41NzMtMTMuNzE1LTMwLjU3My0zMC41NzNDMjQ0LjM1NCw3OC4xNDIsMjU4LjA2OSw2NC40MjcsMjc0LjkyNyw2NC40MjcgTTI3NC45MjcsNjEuNDI3DQoJCQljLTE4LjU0MiwwLTMzLjU3MywxNS4wMzEtMzMuNTczLDMzLjU3MmMwLDE4LjU0MiwxNS4wMzEsMzMuNTczLDMzLjU3MywzMy41NzNjMTguNTQyLDAsMzMuNTczLTE1LjAzMSwzMy41NzMtMzMuNTczDQoJCQlDMzA4LjUsNzYuNDU5LDI5My40NjksNjEuNDI3LDI3NC45MjcsNjEuNDI3TDI3NC45MjcsNjEuNDI3eiIvPg0KCTwvZz4NCgk8dGV4dCB0cmFuc2Zvcm09Im1hdHJpeCgxIDAgMCAxIDEzOS41IDEwMikiPjx0c3BhbiB4PSIwIiB5PSIwIiBmaWxsPSIjQzBDMEMwIiBmb250LWZhbWlseT0iJ09DUkFTdGQnIiBmb250LXNpemU9IjE4Ij5YWFhYWFhYICA8L3RzcGFuPjx0c3BhbiB4PSIxMTYuNjM4IiB5PSIwIiBmaWxsPSIjRkYwMDAwIiBmb250LWZhbWlseT0iJ09DUkFTdGQnIiBmb250LXNpemU9IjE4Ij5YWFg8L3RzcGFuPjwvdGV4dD4NCjwvZz4NCjwvc3ZnPg0K" alt="">
            <div class="stripe-payment-card-cvc-infotext">
                <strong>{s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/visa_mastercard/title}{/s}:</strong> {s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/visa_mastercard/message}{/s}
            </div>
        </div>
        <div class="stripe-payment-amex stripe-payment-card-cvc-info-popup-cardtype">
            <div class="stripe-payment-card-cvc-header">
                <span class="stripe-payment-card-cvc-header-title">
                    {s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/amex/header/title/card}{/s}<br>
                    <strong>{s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/amex/header/title/back}{/s}</strong>
                </span>
                <div class="stripe-payment-card-cvc-header-logos">
                    <div class="stripe-payment-provider-logo american-express"></div>
                </div>
            </div>
            <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNy4wLjIsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB3aWR0aD0iMzEwcHgiIGhlaWdodD0iMTgwcHgiIHZpZXdCb3g9IjAgMCAzMTAgMTgwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAzMTAgMTgwIiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxnPg0KCQk8cGF0aCBmaWxsPSIjRjBGMEYwIiBkPSJNMjAsMTc5LjVjLTcuOTk1LDAtMTQuNS02LjIzNS0xNC41LTEzLjg5OVYxNC40QzUuNSw2LjczNSwxMi4wMDUsMC41LDIwLDAuNWgyNzANCgkJCWM3Ljk5NSwwLDE0LjUsNi4yMzUsMTQuNSwxMy45djE1MS4yMDFjMCw3LjY2NC02LjUwNSwxMy44OTktMTQuNSwxMy44OTlIMjB6Ii8+DQoJCTxwYXRoIGZpbGw9IiNFMEUwRTAiIGQ9Ik0yOTAsMWM3LjcyLDAsMTQsNi4wMTEsMTQsMTMuNHYxNTEuMmMwLDcuMzg5LTYuMjgsMTMuNC0xNCwxMy40SDIwYy03LjcyLDAtMTQtNi4wMTEtMTQtMTMuNFYxNC40DQoJCQlDNiw3LjAxMSwxMi4yOCwxLDIwLDFIMjkwIE0yOTAsMEgyMEMxMS43MTYsMCw1LDYuNDQ3LDUsMTQuNHYxNTEuMmMwLDcuOTUzLDYuNzE2LDE0LjQsMTUsMTQuNGgyNzBjOC4yODUsMCwxNS02LjQ0NywxNS0xNC40DQoJCQlWMTQuNEMzMDUsNi40NDcsMjk4LjI4NSwwLDI5MCwwTDI5MCwweiIvPg0KCTwvZz4NCgk8dGV4dCB0cmFuc2Zvcm09Im1hdHJpeCgxIDAgMCAxIDMyIDExNCkiIGZpbGw9IiNDMEMwQzAiIGZvbnQtZmFtaWx5PSInT0NSQVN0ZCciIGZvbnQtc2l6ZT0iMTgiPlhYWFggWFhYWCBYWFhYIFhYWFg8L3RleHQ+DQoJPGc+DQoJCTxwYXRoIGZpbGw9IiNGRkZGRkYiIGQ9Ik0yNjMuNDI3LDExNS4wNzNjLTE3LjY4NiwwLTMyLjA3My0xNC4zODktMzIuMDczLTMyLjA3NGMwLTE3LjY4NSwxNC4zODgtMzIuMDcyLDMyLjA3My0zMi4wNzINCgkJCVMyOTUuNSw2NS4zMTUsMjk1LjUsODNDMjk1LjUsMTAwLjY4NSwyODEuMTEyLDExNS4wNzMsMjYzLjQyNywxMTUuMDczeiIvPg0KCQk8cGF0aCBmaWxsPSIjMzMzMzMzIiBkPSJNMjYzLjQyNyw1Mi40MjdjMTYuODU4LDAsMzAuNTczLDEzLjcxNSwzMC41NzMsMzAuNTcyYzAsMTYuODU4LTEzLjcxNSwzMC41NzMtMzAuNTczLDMwLjU3Mw0KCQkJYy0xNi44NTgsMC0zMC41NzMtMTMuNzE1LTMwLjU3My0zMC41NzNDMjMyLjg1Myw2Ni4xNDIsMjQ2LjU2OSw1Mi40MjcsMjYzLjQyNyw1Mi40MjcgTTI2My40MjcsNDkuNDI3DQoJCQljLTE4LjU0MiwwLTMzLjU3MywxNS4wMzEtMzMuNTczLDMzLjU3MmMwLDE4LjU0MiwxNS4wMzEsMzMuNTczLDMzLjU3MywzMy41NzNjMTguNTQyLDAsMzMuNTczLTE1LjAzMSwzMy41NzMtMzMuNTczDQoJCQlDMjk3LDY0LjQ1OSwyODEuOTY5LDQ5LjQyNywyNjMuNDI3LDQ5LjQyN0wyNjMuNDI3LDQ5LjQyN3oiLz4NCgk8L2c+DQoJPHRleHQgdHJhbnNmb3JtPSJtYXRyaXgoMSAwIDAgMSAyNDcgODkpIiBmaWxsPSIjRkYwMDAwIiBmb250LWZhbWlseT0iJ09DUkFTdGQnIiBmb250LXNpemU9IjE0Ij5YWFg8L3RleHQ+DQo8L2c+DQo8L3N2Zz4NCg==" alt="">
            <div class="stripe-payment-card-cvc-infotext">
                <strong>{s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/amex/title}{/s}:</strong> {s namespace=frontend/plugins/payment/stripe_payment/card name=cvc_info/amex/message}{/s}
            </div>
        </div>
    </div>
{/strip}
