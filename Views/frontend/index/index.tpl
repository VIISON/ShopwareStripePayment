{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_header_javascript"}
    {$smarty.block.parent}

    <script type="text/javascript">
        if (typeof document.asyncReady === 'function') {
            // Shopware >= 5.3, hence wait for async JavaScript first
            document.stripeJQueryReady = function(callback) {
                document.asyncReady(function() {
                    $(document).ready(callback);
                });
            };
        } else {
            // Shopware < 5.3, hence just wait for jQuery to be ready
            document.stripeJQueryReady = function(callback) {
                $(document).ready(callback);
            };
        }
    </script>
{/block}

{block name="frontend_index_javascript_async_ready"}
    {* This block is only loaded in Shopware >= 5.3 *}
    <script type="text/javascript">
        (function () {
            // Check for any JavaScript that is being loaded asynchronously, but neither rely on the availability of
            // the 'document.asyncReady' function nor the '$theme.asyncJavascriptLoading' Smarty variable. The reason
            // for this is that 'document.asyncReady' is always defined, even if '$theme.asyncJavascriptLoading' is
            // falsey. Hence the only way to reliably detect async scrips is by checking the respective DOM element for
            // the 'async' attribute.
            var mainScriptElement = document.getElementById('main-script');
            var isAsyncJavascriptLoadingEnabled = mainScriptElement && mainScriptElement.hasAttribute('async');
            if (!isAsyncJavascriptLoadingEnabled && typeof document.asyncReady === 'function' && asyncCallbacks) {
                // Async loading is disabled, hence we manually call all queued async  callbacks, because Shopware just
                // ignores them in this case
                for (var i = 0; i < asyncCallbacks.length; i++) {
                    if (typeof asyncCallbacks[i] === 'function') {
                        asyncCallbacks[i].call(document);
                    }
                }
            }
        })();
    </script>

    {$smarty.block.parent}
{/block}
