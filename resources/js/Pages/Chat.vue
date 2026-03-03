<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUpdated, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    conversations: Array,
    activeConversation: Object,
    messages: Array
});

// COPIAS LOCALES REACTIVAS
const localConversations = ref([]);
const localMessages = ref([]);

watch(() => props.conversations, (newVal) => { localConversations.value = newVal ? [...newVal] : []; }, { immediate: true });
watch(() => props.messages, (newVal) => { localMessages.value = newVal ? [...newVal] : []; }, { immediate: true });

const messagesContainer = ref(null);
const fileInput = ref(null);
const showAttachmentMenu = ref(false);
const acceptedFileTypes = ref('*');

// ==========================================
// 📞 ESTADOS DE LLAMADA (WebRTC)
// ==========================================
const showCallNotification = ref(false);
const incomingCallData = ref(null);
const peerConnection = ref(null);
const localStream = ref(null);
const isCallActive = ref(false);

const form = useForm({
    type: 'text',
    body: '',
    media: null,
});

const selectChat = (id) => {
    router.get(route('chat'), { chat: id }, { preserveState: true, preserveScroll: true });
};

const takeChat = (conversationId) => {
    router.post(route('chat.assign', conversationId), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const isWindowOpen = computed(() => {
    if (!props.activeConversation?.last_message_at) return false;
    const lastMsgDate = new Date(props.activeConversation.last_message_at);
    const diffInHours = (new Date() - lastMsgDate) / (1000 * 60 * 60);
    return diffInHours < 24;
});

// ==========================================
// 🚀 LÓGICA DE LLAMADAS (CONTESTAR, RECHAZAR, COLGAR)
// ==========================================

const sanitizeSdp = (sdp) => {
    if (!sdp) return sdp;
    let lines = sdp.replace(/\r\n/g, '\n').split('\n');
    let cleanLines = lines.filter(line => {
        let tLine = line.trim();
        if (!tLine) return false;
        if (tLine.includes('a=ssrc:') && tLine.includes('cname:WhatsAppAudioStream1')) {
            console.warn("Línea ssrc problemática eliminada.");
            return false; 
        }
        return true;
    }).map(line => line.trim());
    return cleanLines.join('\r\n') + '\r\n';
};

const answerCall = async () => {
    try {
        console.log("1. Solicitando micrófono...");
        localStream.value = await navigator.mediaDevices.getUserMedia({ audio: true });

        console.log("2. Creando PeerConnection...");
        peerConnection.value = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        });

        // REPRODUCIR EL AUDIO DE META
        peerConnection.value.ontrack = (event) => {
            console.log("🎵 ¡Pista de audio recibida de Meta!");
            const remoteAudio = document.getElementById('remoteAudio');
            if (remoteAudio) {
                remoteAudio.srcObject = event.streams[0];
                remoteAudio.play().catch(e => console.error("Error al reproducir audio:", e));
            }
        };

        localStream.value.getTracks().forEach(track => {
            peerConnection.value.addTrack(track, localStream.value);
        });

        console.log("3. Limpiando y configurando Oferta Remota (SDP)...");
        const rawSdp = incomingCallData.value.session.sdp;
        const cleanSdp = sanitizeSdp(rawSdp);

        await peerConnection.value.setRemoteDescription(new RTCSessionDescription({
            type: 'offer',
            sdp: cleanSdp
        }));

        console.log("4. Creando Respuesta Local...");
        const answer = await peerConnection.value.createAnswer();
        await peerConnection.value.setLocalDescription(answer);

        console.log("5. Enviando Respuesta a Laravel/Meta...");
        
        axios.post(route('chat.call.accept', props.activeConversation.id), {
            call_id: incomingCallData.value.id,
            sdp: answer.sdp
        }).then(response => {
            console.log("✅ ¡Llamada conectada exitosamente en Meta!", response.data);
            showCallNotification.value = false;
            isCallActive.value = true;
        }).catch(err => {
            console.error("Error del servidor al aceptar la llamada:", err.response?.data || err);
            alert("Meta rechazó la llamada. Revisa la consola.");
            hangUpLocal();
        });

    } catch (err) {
        console.error("Fallo crítico al contestar:", err);
        alert("Error de WebRTC: " + err.message);
        hangUpLocal();
    }
};

// 🔴 NUEVA FUNCIÓN: Rechazar activamente la llamada
const rejectCallAction = () => {
    if (!incomingCallData.value) return;

    console.log("Enviando rechazo a Meta...");
    axios.post(route('chat.call.reject', props.activeConversation.id), {
        call_id: incomingCallData.value.id
    }).then(() => {
        console.log("❌ Llamada rechazada exitosamente en Meta.");
    }).catch(err => {
        console.error("Error al rechazar la llamada:", err);
    });

    hangUpLocal(); 
};

// 🧹 FUNCIÓN DE LIMPIEZA: Apaga el micrófono y esconde la UI
const hangUpLocal = () => {
    if (peerConnection.value) peerConnection.value.close();
    if (localStream.value) localStream.value.getTracks().forEach(t => t.stop());
    isCallActive.value = false;
    showCallNotification.value = false;
    incomingCallData.value = null;
    console.log("Recursos de llamada liberados localmente.");
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('waba.chat')
            .listen('MessageReceived', (e) => {
                
                // CONTROL DE ESTADOS DE LLAMADA
                if (e.message.type === 'call') {
                    const callEvent = e.message.data?.event;
                    
                    if (callEvent === 'connect') {
                        console.log("¡Llamada de Meta detectada!");
                        incomingCallData.value = e.message.data;
                        showCallNotification.value = true;
                    } 
                    else if (callEvent === 'terminate') {
                        console.log("Meta informa que la llamada terminó (terminate).");
                        hangUpLocal(); // Escondemos los botones inmediatamente si el cliente cuelga
                    }
                }

                if (props.activeConversation && e.message.conversation_id === props.activeConversation.id) {
                    localMessages.value.push(e.message);
                    scrollToBottom();
                }
                const chatSide = localConversations.value.find(c => c.id === e.message.conversation_id);
                if (chatSide) chatSide.last_message_at = e.message.created_at;
            });
    }
});

const openFilePicker = (type) => {
    showAttachmentMenu.value = false;
    if (type === 'media') acceptedFileTypes.value = 'image/jpeg, image/png, video/mp4, video/3gpp';
    else if (type === 'sticker') acceptedFileTypes.value = 'image/webp, .webp';
    else if (type === 'document') acceptedFileTypes.value = '.pdf, .doc, .docx, .xls, .xlsx, .txt';
    else if (type === 'audio') acceptedFileTypes.value = 'audio/aac, audio/mp4, audio/mpeg, audio/ogg, audio/mp3';
    else acceptedFileTypes.value = '*';
    nextTick(() => { if (fileInput.value) fileInput.value.click(); });
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.media = file;
        if (file.name.endsWith('.webp') || file.type === 'image/webp') form.type = 'sticker';
        else if (file.type.startsWith('image/')) form.type = 'image';
        else if (file.type.startsWith('video/')) form.type = 'video';
        else if (file.type.startsWith('audio/')) form.type = 'audio';
        else form.type = 'document';
    }
};

const removeFile = () => {
    form.media = null;
    form.type = 'text';
    if (fileInput.value) fileInput.value.value = '';
};

const sendMessage = () => {
    if ((!form.body.trim() && !form.media) || !isWindowOpen.value) return;
    const tempId = Date.now();
    localMessages.value.push({
        id: tempId, body: form.body, type: form.type, outgoing: true, status: 'sending', created_at: new Date().toISOString(), media_url: form.media ? URL.createObjectURL(form.media) : null
    });
    scrollToBottom();
    const currentBody = form.body;
    form.post(route('chat.send', props.activeConversation.id), {
        preserveScroll: true,
        onSuccess: () => { form.reset('body', 'media', 'type'); removeFile(); scrollToBottom(); },
        onError: () => { localMessages.value = localMessages.value.filter(m => m.id !== tempId); form.body = currentBody; }
    });
};

const scrollToBottom = () => {
    nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; });
};

onUpdated(() => scrollToBottom());
</script>

<template>
    <Head title="Chat Experimental de Voz" />

    <AuthenticatedLayout>
        
        <audio id="remoteAudio" autoplay class="hidden"></audio>

        <div v-if="showCallNotification" class="fixed top-4 right-4 z-50 bg-[#1e2b33] text-white px-5 py-4 rounded-2xl shadow-2xl border border-[#313d45] flex flex-col gap-4 min-w-[300px]">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-[#00a884] rounded-full flex items-center justify-center animate-bounce">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                </div>
                <div>
                    <p class="font-bold text-base">Llamada de WhatsApp</p>
                    <p class="text-xs text-[#8696a0]">Conexión de voz detectada...</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="answerCall" class="flex-1 bg-[#00a884] hover:bg-[#008f6f] text-white py-2 rounded-lg font-bold transition-colors">Contestar</button>
                <button @click="rejectCallAction" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg font-bold transition-colors">Rechazar</button>
            </div>
        </div>

        <div v-if="isCallActive" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-[#00a884] text-white px-6 py-2 rounded-full shadow-lg flex items-center gap-4">
            <span class="animate-pulse font-medium">● En llamada activa</span>
            <button @click="hangUpLocal" class="bg-red-500 px-3 py-1 rounded-full text-xs font-bold hover:bg-red-600 transition-colors">Colgar</button>
        </div>

        <div class="max-w-7xl mx-auto h-[calc(100vh-100px)] flex border border-gray-200 dark:border-[#313d45] bg-white dark:bg-[#111b21] shadow-sm overflow-hidden mt-4 rounded-xl transition-colors duration-300">
            
            <div class="w-1/3 border-r border-gray-200 dark:border-[#313d45] flex flex-col bg-white dark:bg-[#111b21]">
                <div class="p-4 flex justify-between items-center bg-gray-50/50 dark:bg-[#202c33]">
                    <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-xl tracking-tight">Chats (Lab)</h2>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div v-if="localConversations.length === 0" class="p-8 text-center text-gray-500 dark:text-[#8696a0] text-sm">No hay conversaciones aún.</div>

                    <div v-for="conv in localConversations" :key="conv.id"
                         @click="selectChat(conv.id)"
                         :class="['p-3 flex items-start gap-3 cursor-pointer transition-colors duration-200 border-b border-transparent dark:border-[#202c33]', activeConversation?.id === conv.id ? 'bg-[#f0f2f5] dark:bg-[#2a3942]' : 'hover:bg-gray-50 dark:hover:bg-[#202c33]']">
                        <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-[#6b7c85] flex-shrink-0 flex items-center justify-center text-slate-600 dark:text-[#e9edef] font-bold text-lg mt-0.5">
                            {{ conv.contact.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-0.5">
                                <h3 class="font-semibold text-gray-900 dark:text-[#e9edef] text-[15px] truncate">{{ conv.contact.name }}</h3>
                                <span :class="['text-xs', activeConversation?.id === conv.id ? 'text-gray-900 dark:text-[#e9edef]' : 'text-gray-500 dark:text-[#8696a0]']">
                                    {{ new Date(conv.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-[13px] text-gray-500 dark:text-[#8696a0] truncate">{{ conv.contact.phone }}</p>
                                <span v-if="conv.user_id === $page.props.auth.user.id" class="text-[9px] font-bold tracking-wide uppercase bg-[#d9fdd3] text-[#005c4b] dark:bg-[#005c4b] dark:text-[#d9fdd3] px-2 py-0.5 rounded-full ml-2 flex-shrink-0">Mío</span>
                            </div>
                            <div v-if="!conv.user_id" class="mt-2">
                                <button @click.stop="takeChat(conv.id)" class="text-[11px] font-medium tracking-wide bg-[#00a884] text-white px-3 py-1 rounded-full hover:bg-[#008f6f] transition-colors shadow-sm">Tomar Chat</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-2/3 flex flex-col relative bg-[#efeae2] dark:bg-[#0b141a]"> 
                <div v-if="showAttachmentMenu" @click="showAttachmentMenu = false" class="absolute inset-0 z-30"></div>

                <template v-if="activeConversation">
                    <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center gap-3 shadow-sm z-10">
                        <div class="w-10 h-10 rounded-full bg-slate-400 dark:bg-[#6b7c85] flex items-center justify-center text-white dark:text-[#e9edef] font-bold">
                            {{ activeConversation.contact.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1">
                            <h2 class="font-semibold text-gray-900 dark:text-[#e9edef] leading-tight">{{ activeConversation.contact.name }}</h2>
                            <p class="text-xs text-gray-500 dark:text-[#8696a0]">{{ activeConversation.contact.phone }}</p>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-2 flex flex-col z-10" ref="messagesContainer">
                        <div class="mt-auto"></div>

                        <template v-for="msg in localMessages" :key="msg.id">
                            <div v-if="msg.type !== 'reaction'" :class="['flex mb-2', msg.type === 'call' ? 'justify-center w-full' : (msg.outgoing ? 'justify-end' : 'justify-start')]">
                                
                                <div v-if="msg.type === 'call'" class="bg-white dark:bg-[#202c33] px-4 py-2 rounded-lg shadow-sm flex items-center gap-4 max-w-[85%] border border-gray-200 dark:border-[#313d45] my-2">
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', msg.data?.event === 'terminate' ? 'bg-green-50 dark:bg-green-900/20 text-green-500' : 'bg-red-50 dark:bg-red-900/20 text-red-500']">
                                        <svg v-if="msg.data?.event === 'terminate'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-[15px] text-gray-800 dark:text-[#e9edef]">{{ msg.body }}</p>
                                        <p class="text-[12px] text-gray-500 dark:text-[#8696a0] mt-0.5">
                                            {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                            <span v-if="msg.data?.duration" class="ml-2 bg-gray-100 dark:bg-[#111b21] px-1.5 py-0.5 rounded">{{ Math.floor(msg.data.duration / 60) }}:{{ (msg.data.duration % 60).toString().padStart(2, '0') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div v-else :class="['max-w-[75%] rounded-lg px-2.5 py-1.5 text-[14.5px] shadow-sm relative flex flex-col', msg.type === 'sticker' ? 'bg-transparent shadow-none' : (msg.outgoing ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-[#e9edef] rounded-tr-none' : 'bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] rounded-tl-none')]">
                                    <template v-if="msg.media_url">
                                        <img v-if="msg.type === 'sticker'" :src="msg.media_url.startsWith('blob:') ? msg.media_url : `/storage/${msg.media_url}`" class="mb-1 w-32 h-32 object-contain drop-shadow-md" />
                                        <img v-else-if="msg.type === 'image'" :src="msg.media_url.startsWith('blob:') ? msg.media_url : `/storage/${msg.media_url}`" class="rounded-[4px] mt-1 mb-1 max-w-full h-auto max-h-72 object-contain" />
                                        <video v-else-if="msg.type === 'video'" :src="`/storage/${msg.media_url}`" controls class="rounded-[4px] mt-1 mb-1 max-w-full h-auto max-h-64 bg-black"></video>
                                        <audio v-else-if="msg.type === 'audio'" :src="`/storage/${msg.media_url}`" controls class="my-1 w-full max-w-[250px] h-10"></audio>
                                    </template>
                                    <div class="flex items-end gap-2 flex-wrap relative">
                                        <p v-if="msg.body" :class="['whitespace-pre-wrap leading-relaxed', msg.type === 'sticker' ? 'bg-white dark:bg-[#202c33] p-2 rounded-lg shadow-sm mt-1' : 'pt-1']">{{ msg.body }}</p>
                                        <div :class="['flex items-center gap-1 ml-auto mt-1', msg.type === 'sticker' ? 'bg-white/80 dark:bg-black/40 px-1 rounded' : '']">
                                            <span class="text-[10.5px] text-[#667781] dark:text-[#8696a0] leading-none">{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center gap-3 z-40 relative">
                        <div class="relative">
                            <button @click="showAttachmentMenu = !showAttachmentMenu" type="button" :disabled="!isWindowOpen || form.processing" class="text-[#54656f] dark:text-[#8696a0] hover:bg-gray-200 dark:hover:bg-[#2a3942] transition-colors p-2 rounded-full disabled:opacity-50">
                                <svg class="w-6 h-6 transform rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            </button>
                            <div v-if="showAttachmentMenu" class="absolute bottom-14 left-0 bg-white dark:bg-[#202c33] shadow-lg rounded-2xl py-3 w-56 border border-gray-100 dark:border-[#313d45]">
                                <ul class="flex flex-col gap-1">
                                    <li><button @click="openFilePicker('media')" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21]">Fotos y videos</button></li>
                                </ul>
                            </div>
                        </div>

                        <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" :accept="acceptedFileTypes" />

                        <form @submit.prevent="sendMessage" class="flex-1 flex items-center gap-3">
                            <div class="flex-1 relative rounded-lg bg-white dark:bg-[#2a3942] overflow-hidden shadow-sm">
                                <input v-model="form.body" type="text" :disabled="!isWindowOpen || form.processing" :placeholder="isWindowOpen ? 'Escribe un mensaje' : 'Ventana cerrada.'" class="w-full border-0 focus:ring-0 px-4 py-2.5 text-[15px] bg-transparent text-gray-800 dark:text-[#e9edef] disabled:bg-gray-100" />
                            </div>
                            <button type="submit" :disabled="!isWindowOpen || (!form.body && !form.media) || form.processing" class="p-2.5 rounded-full text-white transition-colors bg-[#00a884] hover:bg-[#008f6f] disabled:opacity-0">
                                <svg class="w-5 h-5 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                            </button>
                        </form>
                    </div>
                </template>

                <div v-else class="flex-1 flex flex-col items-center justify-center bg-[#f0f2f5] dark:bg-[#202c33] border-l border-gray-200 dark:border-[#313d45]">
                    <div class="text-center w-80">
                        <h2 class="text-2xl font-light text-[#41525d] dark:text-[#e9edef] mb-4">WhatsApp Web</h2>
                        <p class="text-[14px] text-[#8696a0]">Selecciona un chat para comenzar a enviar mensajes y hacer pruebas de voz.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>