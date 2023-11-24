@props([
    'toUser' => '',
    'subject' => '',
    'message' => '',
])

<x-app-layout
    pageTitle="New Message"
    pageDescription="Create a new message"
>
    <x-message.breadcrumbs currentPage="New Message" />

    <div class="w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">New Message</h1>
    </div>

    <form action='/request/message/create.php' method='post' x-data='{ isValid: true }'>
        {{ csrf_field() }}
        <div><table class='mb-4'><tbody>
            <tr>
                <td><label for='recipient'>User:</label></td>
                <td>
                    <div class="w-full">
                        <div style="float:right">
                            <x-input.user-select-image for="recipient" :user="$toUser" size="48" />
                        </div>
                        <!-- TODO: why won't this field align properly? -->
                        <div style='vertical-align:middle'>
                            <x-input.user-select name="recipient" :user="$toUser" />
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td><label for='title'>Subject:</label></td>
                <td><input class='w-full' type='text' value='{{ $subject }}' id='title' name='title' required /></td>
            </tr>

            <tr>
                <td><label for='commentTextarea'>Message:</label></td>
                <td>
                    <x-input.shortcode-textarea
                        name='body'
                        watermark='Enter your message here...'
                        initialValue="{{ $message }}"
                    />
                </td>
            </tr>
        </tbody></table></div>
    </form>

</x-app-layout>
