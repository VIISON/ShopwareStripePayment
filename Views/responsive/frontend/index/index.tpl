{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_header_javascript" append}
    <script type="text/javascript">
        if (typeof document.asyncReady === 'function') {
            document.stripeJQueryReady = function(callback) {
                document.asyncReady(function() {
                    $(document).ready(callback);
                });
            };
        } else {
            document.stripeJQueryReady = function(callback) {
                $(document).ready(callback);
            };
        }
    </script>
{/block}

{block name="frontend_index_javascript_async_ready" prepend}
    <script type="text/javascript">
        (function () {
            var mainScriptElement = document.getElementById('main-script');
            var isAsyncJavascriptLoadingEnabled = mainScriptElement && mainScriptElement.hasAttribute('async');
            if (!isAsyncJavascriptLoadingEnabled && typeof document.asyncReady === 'function' && asyncCallbacks) {
                for (var i = 0; i < asyncCallbacks.length; i++) {
                    if (typeof asyncCallbacks[i] === 'function') {
                        asyncCallbacks[i].call(document);
                    }
                }
            }
        })();
    </script>
{/block}
