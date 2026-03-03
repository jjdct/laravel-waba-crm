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
// 🎙️ ESTADOS DE NOTA DE VOZ
// ==========================================
const isRecording = ref(false);
const recordingTime = ref(0);
let mediaRecorder = null;
let audioChunks = [];
let recordingInterval = null;

const formattedRecordingTime = computed(() => {
    const m = Math.floor(recordingTime.value / 60);
    const s = recordingTime.value % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
});

// ==========================================
// 📱 ESTADOS DE MODALES (UBICACIÓN Y BOTONES)
// ==========================================
const showLocationModal = ref(false);
const showButtonModal = ref(false);

const locationData = ref({ lat: '', lng: '' });
const buttonData = ref({ body: '', btn1: '', btn2: '', btn3: '' });

// ==========================================
// 📞 ESTADOS DE LLAMADA (WebRTC)
// ==========================================
const showCallNotification = ref(false);
const incomingCallData = ref(null);
const peerConnection = ref(null);
const localStream = ref(null);
const isCallActive = ref(false);
const callDuration = ref(0);
let callTimer = null;

const formattedCallDuration = computed(() => {
    const minutes = Math.floor(callDuration.value / 60);
    const seconds = callDuration.value % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const startCallTimer = () => {
    callDuration.value = 0;
    callTimer = setInterval(() => { callDuration.value++; }, 1000);
};

const stopCallTimer = () => {
    if (callTimer) clearInterval(callTimer);
    callTimer = null;
};

// ==========================================
// 📝 FORMULARIO EXPANDIDO
// ==========================================
const form = useForm({
    type: 'text',
    body: '',
    media: null,
    lat: null,         
    lng: null,         
    buttons: null,     
    message_id: null,  
    emoji: null        
});

const getReactionForMessage = (messageWamId) => {
    return localMessages.value.find(m => m.type === 'reaction' && m.data?.reaction_to_message_id === messageWamId);
};

const selectChat = (id) => {
    router.get(route('dashboard'), { chat: id }, { preserveState: true, preserveScroll: true });
};

// 📱 NUEVO: CIERRA EL CHAT EN MÓVIL
const closeChat = () => {
    router.get(route('dashboard'), {}, { preserveState: true, preserveScroll: true });
};

const takeChat = (conversationId) => {
    router.post(route('chat.assign', conversationId), {}, { preserveScroll: true, preserveState: true });
};

const isWindowOpen = computed(() => {
    if (!props.activeConversation?.last_message_at) return false;
    const lastMsgDate = new Date(props.activeConversation.last_message_at);
    return ((new Date() - lastMsgDate) / (1000 * 60 * 60)) < 24;
});

// ==========================================
// 🎙️ LÓGICA DE GRABACIÓN DE VOZ
// ==========================================
const toggleVoiceRecording = async () => {
    if (isRecording.value) {
        mediaRecorder.stop();
        isRecording.value = false;
        clearInterval(recordingInterval);
    } else {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/ogg; codecs=opus' }); 
                const file = new File([audioBlob], 'voice_note.ogg', { type: 'audio/ogg' });
                
                form.media = file;
                form.type = 'audio';
                sendMessage(); 
                
                stream.getTracks().forEach(track => track.stop()); 
            };

            mediaRecorder.start();
            isRecording.value = true;
            recordingTime.value = 0;
            recordingInterval = setInterval(() => { recordingTime.value++; }, 1000);
        } catch (err) {
            alert("Necesitas dar permisos de micrófono para enviar notas de voz.");
            console.error(err);
        }
    }
};

const cancelVoiceRecording = () => {
    if (mediaRecorder && isRecording.value) {
        mediaRecorder.onstop = null; 
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        isRecording.value = false;
        clearInterval(recordingInterval);
        audioChunks = [];
    }
};

// ==========================================
// 🚀 LÓGICA DE MODALES
// ==========================================
const openLocationModal = () => {
    showAttachmentMenu.value = false;
    locationData.value = { lat: '', lng: '' };
    showLocationModal.value = true;
};

const submitLocation = () => {
    if (!locationData.value.lat || !locationData.value.lng) return;
    form.type = 'location';
    form.lat = locationData.value.lat;
    form.lng = locationData.value.lng;
    sendMessage();
    showLocationModal.value = false;
};

const openButtonModal = () => {
    showAttachmentMenu.value = false;
    buttonData.value = { body: '', btn1: '', btn2: '', btn3: '' };
    showButtonModal.value = true;
};

const submitButtons = () => {
    if (!buttonData.value.body || !buttonData.value.btn1) return;
    form.type = 'interactive';
    form.body = buttonData.value.body;
    
    let btns = {};
    if (buttonData.value.btn1) btns['btn_1'] = buttonData.value.btn1;
    if (buttonData.value.btn2) btns['btn_2'] = buttonData.value.btn2;
    if (buttonData.value.btn3) btns['btn_3'] = buttonData.value.btn3;
    
    form.buttons = btns;
    sendMessage();
    showButtonModal.value = false;
};

const reactToMessage = (wamId) => {
    form.type = 'reaction';
    form.message_id = wamId;
    form.emoji = '❤️';
    sendMessage();
};

// ==========================================
// 📞 LÓGICA DE LLAMADAS
// ==========================================
const sanitizeSdp = (sdp) => {
    if (!sdp) return sdp;
    let lines = sdp.replace(/\r\n/g, '\n').split('\n');
    let cleanLines = lines.filter(line => {
        let tLine = line.trim();
        if (!tLine) return false;
        if (tLine.includes('a=ssrc:') && tLine.includes('cname:WhatsAppAudioStream1')) return false; 
        return true;
    }).map(line => line.trim());
    return cleanLines.join('\r\n') + '\r\n';
};

const answerCall = async () => {
    try {
        localStream.value = await navigator.mediaDevices.getUserMedia({ audio: true });
        peerConnection.value = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });

        peerConnection.value.ontrack = (event) => {
            const remoteAudio = document.getElementById('remoteAudio');
            if (remoteAudio) {
                remoteAudio.srcObject = event.streams[0];
                remoteAudio.play().catch(e => console.error(e));
            }
        };

        localStream.value.getTracks().forEach(track => peerConnection.value.addTrack(track, localStream.value));

        const cleanSdp = sanitizeSdp(incomingCallData.value.session.sdp);
        await peerConnection.value.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: cleanSdp }));

        const answer = await peerConnection.value.createAnswer();
        await peerConnection.value.setLocalDescription(answer);

        axios.post(route('chat.call.accept', props.activeConversation.id), {
            call_id: incomingCallData.value.id,
            sdp: answer.sdp
        }).then(() => {
            showCallNotification.value = false;
            isCallActive.value = true;
            startCallTimer();
        }).catch(() => {
            alert("Meta rechazó la llamada.");
            hangUpLocal();
        });
    } catch (err) {
        alert("Error de WebRTC: " + err.message);
        hangUpLocal();
    }
};

const rejectCallAction = () => {
    if (!incomingCallData.value) return;
    axios.post(route('chat.call.reject', props.activeConversation.id), { call_id: incomingCallData.value.id }).catch(err => console.error(err));
    hangUpLocal(); 
};

const hangUpLocal = () => {
    if (peerConnection.value) peerConnection.value.close();
    if (localStream.value) localStream.value.getTracks().forEach(t => t.stop());
    isCallActive.value = false;
    showCallNotification.value = false;
    incomingCallData.value = null;
    stopCallTimer();
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('waba.chat')
            .listen('MessageReceived', (e) => {
                if (e.message.type === 'call') {
                    if (e.message.data?.event === 'connect') {
                        incomingCallData.value = e.message.data;
                        showCallNotification.value = true;
                    } else if (e.message.data?.event === 'terminate') {
                        hangUpLocal(); 
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
    if ((!form.body.trim() && !form.media && !form.lat && !form.buttons && !form.emoji) || !isWindowOpen.value) return;
    const tempId = Date.now();

    let optimisticBody = form.body;
    if (form.type === 'location') optimisticBody = "📍 Ubicación enviada";
    if (form.type === 'reaction') optimisticBody = form.emoji;

    localMessages.value.push({
        id: tempId,
        body: optimisticBody,
        type: form.type,
        outgoing: true,
        status: 'sending',
        created_at: new Date().toISOString(),
        media_url: form.media ? URL.createObjectURL(form.media) : null,
        data: form.type === 'reaction' ? { reaction_to_message_id: form.message_id } : null
    });
    scrollToBottom();

    const currentType = form.type;
    const currentBody = form.body;

    form.post(route('dashboard.send', props.activeConversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.type = 'text'; 
            removeFile();
            scrollToBottom();
        },
        onError: () => {
            localMessages.value = localMessages.value.filter(m => m.id !== tempId);
            form.body = currentBody;
            form.type = currentType;
        }
    });
};

const scrollToBottom = () => {
    nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; });
};

onUpdated(() => scrollToBottom());
</script>

<template>
    <Head title="Chat Dashboard" />

    <AuthenticatedLayout>
        
        <audio id="remoteAudio" autoplay class="hidden"></audio>

        <div v-if="showLocationModal" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center backdrop-blur-sm transition-opacity p-4">
            <div class="bg-white dark:bg-[#111b21] rounded-2xl p-6 w-full max-w-[400px] shadow-2xl border border-gray-100 dark:border-[#313d45]">
                <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-[#e9edef] flex items-center gap-2">
                    <span class="text-red-500">📍</span> Enviar Ubicación
                </h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Latitud</label>
                        <input v-model="locationData.lat" type="text" placeholder="Ej: 18.1507609" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Longitud</label>
                        <input v-model="locationData.lng" type="text" placeholder="Ej: -93.1617817" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884]">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showLocationModal = false" class="px-4 py-2 text-gray-600 dark:text-[#8696a0] hover:bg-gray-100 dark:hover:bg-[#202c33] rounded-lg font-medium transition-colors">Cancelar</button>
                    <button @click="submitLocation" :disabled="!locationData.lat || !locationData.lng" class="px-4 py-2 bg-[#00a884] hover:bg-[#008f6f] disabled:opacity-50 text-white rounded-lg font-bold transition-colors shadow-sm">Enviar Mapa</button>
                </div>
            </div>
        </div>

        <div v-if="showButtonModal" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center backdrop-blur-sm transition-opacity p-4">
            <div class="bg-white dark:bg-[#111b21] rounded-2xl p-6 w-full max-w-[400px] shadow-2xl border border-gray-100 dark:border-[#313d45]">
                <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-[#e9edef] flex items-center gap-2">
                    <span class="text-blue-500">🔘</span> Menú Interactivo
                </h3>
                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Texto Principal</label>
                        <textarea v-model="buttonData.body" rows="2" placeholder="¿En qué te podemos ayudar?" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884] resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Botón 1 (Obligatorio)</label>
                        <input v-model="buttonData.btn1" type="text" maxlength="20" placeholder="Ej: Soporte Técnico" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Botón 2 (Opcional)</label>
                        <input v-model="buttonData.btn2" type="text" maxlength="20" placeholder="Ej: Ventas" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-1">Botón 3 (Opcional)</label>
                        <input v-model="buttonData.btn3" type="text" maxlength="20" placeholder="Ej: Cancelar" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#2a3942] dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884]">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showButtonModal = false" class="px-4 py-2 text-gray-600 dark:text-[#8696a0] hover:bg-gray-100 dark:hover:bg-[#202c33] rounded-lg font-medium transition-colors">Cancelar</button>
                    <button @click="submitButtons" :disabled="!buttonData.body || !buttonData.btn1" class="px-4 py-2 bg-[#00a884] hover:bg-[#008f6f] disabled:opacity-50 text-white rounded-lg font-bold transition-colors shadow-sm">Enviar Botones</button>
                </div>
            </div>
        </div>

        <div v-if="showCallNotification" class="fixed top-0 left-0 right-0 z-[100] md:top-4 md:left-auto md:right-4 md:w-[350px] bg-[#111b21] md:bg-[#1e2b33] text-white px-5 py-4 md:rounded-2xl shadow-2xl border-b md:border border-[#313d45] flex flex-col gap-4 animate-[slideDown_0.3s_ease-out]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-[#00a884] rounded-full flex items-center justify-center animate-bounce shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div>
                        <p class="font-bold text-lg leading-tight">Llamada de WhatsApp</p>
                        <p class="text-sm text-[#00a884] font-medium">Entrando...</p>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-2">
                <button @click="rejectCallAction" class="flex-1 bg-[#313d45] hover:bg-red-500/90 text-white py-3 rounded-xl font-bold transition-colors shadow-sm">
                    Rechazar
                </button>
                <button @click="answerCall" class="flex-1 bg-[#00a884] hover:bg-[#008f6f] text-white py-3 rounded-xl font-bold transition-colors shadow-md">
                    Contestar
                </button>
            </div>
        </div>

        <div v-if="isCallActive" class="fixed top-0 left-0 right-0 z-[100] md:top-4 md:left-1/2 md:-translate-x-1/2 md:w-auto bg-[#00a884] text-white px-4 py-3 md:rounded-full shadow-lg flex items-center justify-between md:justify-center md:gap-6 border-b md:border border-[#008f6f] transition-all">
            
            <div class="flex items-center gap-3">
                <div class="relative flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                    <span class="absolute w-full h-full bg-white/40 rounded-full animate-ping"></span>
                    <svg class="w-4 h-4 text-white z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-medium text-white/80 leading-none">En curso</span>
                    <span class="font-bold text-lg leading-tight tracking-wider">{{ formattedCallDuration }}</span>
                </div>
            </div>

            <button @click="hangUpLocal" class="bg-red-500 p-3 rounded-full text-white hover:bg-red-600 transition-colors shadow-md flex items-center justify-center">
                <svg class="w-6 h-6 transform rotate-[135deg]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            </button>
        </div>

        <div class="max-w-7xl mx-auto h-[calc(100vh-100px)] flex border border-gray-200 dark:border-[#313d45] bg-white dark:bg-[#111b21] shadow-sm overflow-hidden mt-4 rounded-xl transition-colors duration-300">
            
            <div :class="['border-r border-gray-200 dark:border-[#313d45] flex-col bg-white dark:bg-[#111b21] w-full md:w-1/3', activeConversation ? 'hidden md:flex' : 'flex']">
                <div class="p-4 flex justify-between items-center bg-gray-50/50 dark:bg-[#202c33]">
                    <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-xl tracking-tight">Chats</h2>
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

            <div :class="['flex-col relative bg-[#efeae2] dark:bg-[#0b141a] w-full md:w-2/3', !activeConversation ? 'hidden md:flex' : 'flex']"> 
                <div v-if="showAttachmentMenu" @click="showAttachmentMenu = false" class="absolute inset-0 z-30"></div>

                <template v-if="activeConversation">
                    <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center gap-3 shadow-sm z-10">
                        
                        <button @click="closeChat" class="md:hidden p-2 -ml-2 mr-1 text-[#54656f] dark:text-[#8696a0] hover:bg-gray-200 dark:hover:bg-[#2a3942] rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </button>

                        <div class="w-10 h-10 rounded-full bg-slate-400 dark:bg-[#6b7c85] flex items-center justify-center text-white dark:text-[#e9edef] font-bold">
                            {{ activeConversation.contact.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1">
                            <h2 class="font-semibold text-gray-900 dark:text-[#e9edef] leading-tight">{{ activeConversation.contact.name }}</h2>
                            <p class="text-xs text-gray-500 dark:text-[#8696a0]">{{ activeConversation.contact.phone }}</p>
                        </div>
                    </div>

                    <div class="text-center py-3 px-4 z-10 my-2">
                        <p class="text-[12px] font-medium inline-flex flex-col items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-300 bg-[#d9fdd3] text-[#111b21] dark:bg-[#182229] dark:text-[#53bdeb] max-w-[85%] mx-auto leading-tight text-center">
                            Estas usando un servicio seguro de Meta para administar este chat.
                        </p>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 md:px-6 py-2 flex flex-col z-10" ref="messagesContainer">
                        <div class="mt-auto"></div>

                        <template v-for="msg in localMessages" :key="msg.id">
                            <div v-if="msg.type !== 'reaction'" :class="['flex mb-2 group', msg.type === 'call' ? 'justify-center w-full' : (msg.outgoing ? 'justify-end' : 'justify-start')]">
                                
                                <div v-if="msg.type === 'call'" class="bg-white dark:bg-[#202c33] px-4 py-2 rounded-lg shadow-sm flex items-center gap-4 max-w-[85%] border border-gray-200 dark:border-[#313d45] my-2">
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', msg.data?.event === 'terminate' ? 'bg-green-50 dark:bg-green-900/20 text-green-500' : 'bg-red-50 dark:bg-red-900/20 text-red-500']">
                                        <svg v-if="msg.data?.event === 'terminate'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-[15px] text-gray-800 dark:text-[#e9edef]">{{ msg.body || 'Llamada de voz' }}</p>
                                        <p class="text-[12px] text-gray-500 dark:text-[#8696a0] mt-0.5">{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</p>
                                    </div>
                                </div>

                                <div v-else :class="['max-w-[85%] md:max-w-[75%] rounded-lg px-2.5 py-1.5 text-[14.5px] shadow-sm relative flex flex-col', msg.type === 'sticker' ? 'bg-transparent shadow-none' : (msg.outgoing ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-[#e9edef] rounded-tr-none' : 'bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] rounded-tl-none')]">
                                    
                                    <button v-if="!msg.outgoing && msg.wam_id && msg.status !== 'sending'" @click="reactToMessage(msg.wam_id)" class="absolute -right-2 md:-right-8 -top-3 md:top-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity bg-white dark:bg-[#202c33] p-1.5 rounded-full shadow border border-gray-100 dark:border-[#313d45] hover:bg-gray-50 dark:hover:bg-[#111b21] z-20">
                                        ❤️
                                    </button>

                                    <template v-if="msg.media_url">
                                        <img v-if="msg.type === 'sticker'" :src="msg.media_url.startsWith('blob:') ? msg.media_url : `/media/secure/${msg.media_url}`" class="mb-1 w-32 h-32 object-contain drop-shadow-md" />
                                        <img v-else-if="msg.type === 'image'" :src="msg.media_url.startsWith('blob:') ? msg.media_url : `/media/secure/${msg.media_url}`" class="rounded-[4px] mt-1 mb-1 max-w-full h-auto max-h-72 object-contain" />
                                        <video v-else-if="msg.type === 'video'" :src="`/media/secure/${msg.media_url}`" controls class="rounded-[4px] mt-1 mb-1 max-w-full h-auto max-h-64 bg-black"></video>
                                        
                                        <div v-else-if="msg.type === 'audio'" class="flex items-center gap-3 bg-black/5 dark:bg-black/20 rounded-xl px-3 py-2 my-1 w-full min-w-[240px] md:min-w-[280px] max-w-[320px] shadow-inner border border-black/5 dark:border-white/5">
                                            <div class="w-10 h-10 rounded-full bg-[#00a884] flex items-center justify-center flex-shrink-0 text-white shadow-sm">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path></svg>
                                            </div>
                                            <audio :src="msg.media_url.startsWith('blob:') ? msg.media_url : `/media/secure/${msg.media_url}`" controls class="w-full h-10 opacity-90"></audio>
                                        </div>

                                        <a v-else-if="msg.type === 'document'" :href="`/media/secure/${msg.media_url}`" target="_blank" :class="['flex items-center gap-3 p-3 rounded-[4px] my-1 transition-colors', msg.outgoing ? 'bg-black/5 dark:bg-black/20 hover:bg-black/10' : 'bg-black/5 dark:bg-black/20 hover:bg-black/10']">
                                            <div class="p-2 rounded-full bg-[#f0f2f5] dark:bg-[#2a3942] text-[#54656f] dark:text-[#8696a0]"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg></div>
                                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold truncate text-gray-800 dark:text-[#e9edef]">Documento</p></div>
                                        </a>
                                    </template>

                                    <div v-if="msg.type === 'location' && msg.data?.location_data" class="flex flex-col items-center gap-2 p-3 bg-blue-50/50 dark:bg-black/20 rounded-lg my-1 border border-blue-100 dark:border-[#313d45]">
                                        <a :href="`https://maps.google.com/?q=$${msg.data.location_data.latitude},${msg.data.location_data.longitude}`" target="_blank" class="flex flex-col items-center text-[#00a884] hover:underline">
                                            <svg class="w-8 h-8 mb-1 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                            <span class="text-sm font-semibold text-gray-800 dark:text-[#e9edef]">Ver en Google Maps</span>
                                        </a>
                                    </div>

                                    <div v-else-if="msg.type === 'button' || msg.type === 'interactive'" class="bg-[#d1f4cc] dark:bg-[#1a382d] text-[#111b21] dark:text-[#e9edef] px-3 py-2 rounded-lg my-1 font-medium text-sm border border-[#4fcc7d]/30 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                        {{ msg.body || msg.data?.interactive?.button_reply?.title || 'Respuesta interactiva' }}
                                    </div>

                                    <div class="flex items-end gap-2 flex-wrap relative">
                                        <p v-if="msg.body && msg.type !== 'interactive' && msg.type !== 'button' && msg.type !== 'location'" :class="['whitespace-pre-wrap leading-relaxed', msg.type === 'sticker' ? 'bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] p-2 rounded-lg shadow-sm mt-1' : 'pt-1']">{{ msg.body }}</p>
                                        
                                        <div v-if="getReactionForMessage(msg.wam_id)" class="absolute -bottom-4 right-0 flex items-center justify-center bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-full px-1.5 py-0.5 shadow-sm transform scale-90 z-20">
                                            <span class="text-[13px] leading-none">{{ getReactionForMessage(msg.wam_id).body }}</span>
                                        </div>

                                        <div :class="['flex items-center gap-1 ml-auto mt-1', msg.type === 'sticker' ? 'bg-white/80 dark:bg-black/40 px-1 rounded' : '']">
                                            <span class="text-[10.5px] text-[#667781] dark:text-[#8696a0] leading-none">{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                            <div v-if="msg.outgoing" class="flex items-center">
                                                <svg v-if="msg.status === 'sending'" class="w-3.5 h-3.5 text-[#667781] dark:text-[#8696a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <svg v-else-if="msg.status === 'sent'" class="w-4 h-4 text-[#667781] dark:text-[#8696a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                <div v-else-if="msg.status === 'delivered'" class="relative w-4 h-4 text-[#667781] dark:text-[#8696a0]">
                                                    <svg class="absolute top-0 left-[-4px] w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><svg class="absolute top-0 left-[2px] w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </div>
                                                <div v-else-if="msg.status === 'read'" class="relative w-4 h-4 text-[#53bdeb] dark:text-[#53bdeb]">
                                                    <svg class="absolute top-0 left-[-4px] w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><svg class="absolute top-0 left-[2px] w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="form.media && !isRecording" class="px-4 md:px-6 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center justify-between border-t border-gray-200 dark:border-[#313d45] z-10">
                        <div class="flex items-center gap-2 text-sm text-[#54656f] dark:text-[#e9edef] font-medium truncate bg-white dark:bg-[#2a3942] px-4 py-2 rounded-lg shadow-sm">
                            <span v-if="form.type === 'sticker'" class="text-green-500">😀</span>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <span class="truncate max-w-[150px] md:max-w-[200px]">{{ form.media.name }}</span>
                        </div>
                        <button @click="removeFile" class="text-[#54656f] dark:text-[#8696a0] hover:text-red-500 transition p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-3 md:px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center gap-2 md:gap-3 z-40 relative">
                        <div class="relative">
                            <button @click="showAttachmentMenu = !showAttachmentMenu" type="button" :disabled="!isWindowOpen || form.processing || isRecording" class="text-[#54656f] dark:text-[#8696a0] hover:bg-gray-200 dark:hover:bg-[#2a3942] transition-colors p-2 rounded-full disabled:opacity-50">
                                <svg class="w-6 h-6 transform rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            </button>

                            <div v-if="showAttachmentMenu" class="absolute bottom-14 left-0 bg-white dark:bg-[#202c33] shadow-lg rounded-2xl py-3 w-56 border border-gray-100 dark:border-[#313d45] transform transition-all z-50">
                                <ul class="flex flex-col gap-1">
                                    <li>
                                        <button @click="openLocationModal" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Enviar Ubicación</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button @click="openButtonModal" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Botones</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button @click="openFilePicker('document')" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-[#7F66FF] flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Documento</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button @click="openFilePicker('media')" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-[#007DFC] flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Fotos y videos</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button @click="openFilePicker('sticker')" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-[#09D261] flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Sticker (.webp)</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button @click="openFilePicker('audio')" type="button" class="w-full text-left px-5 py-2 hover:bg-gray-50 dark:hover:bg-[#111b21] flex items-center gap-4 group">
                                            <div class="w-10 h-10 rounded-full bg-[#FF5353] flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg></div>
                                            <span class="text-[15px] font-medium text-gray-800 dark:text-[#e9edef]">Audio Subido</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" :accept="acceptedFileTypes" />

                        <div v-if="isRecording" class="flex-1 flex items-center justify-between px-3 md:px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg shadow-inner border border-red-200 dark:border-red-800/30">
                            <div class="flex items-center gap-2 md:gap-3">
                                <span class="w-2.5 h-2.5 md:w-3 md:h-3 bg-red-500 rounded-full animate-pulse"></span>
                                <span class="text-red-500 font-bold tracking-wide">{{ formattedRecordingTime }}</span>
                            </div>
                            <div class="flex items-center gap-2 md:gap-4">
                                <button @click="cancelVoiceRecording" class="text-red-500 hover:text-red-700 font-medium text-xs md:text-sm transition-colors">Cancelar</button>
                                <button @click="toggleVoiceRecording" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full transition-colors shadow-md">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                                </button>
                            </div>
                        </div>

                        <form v-else @submit.prevent="sendMessage" class="flex-1 flex items-center gap-2 md:gap-3">
                            <div class="flex-1 relative rounded-lg bg-white dark:bg-[#2a3942] overflow-hidden shadow-sm">
                                <input v-model="form.body" type="text" :disabled="!isWindowOpen || form.processing" :placeholder="isWindowOpen ? 'Escribe un mensaje' : 'Ventana de 24 horas cerrada.'" class="w-full border-0 focus:ring-0 px-3 md:px-4 py-2 md:py-2.5 text-[14px] md:text-[15px] bg-transparent text-gray-800 dark:text-[#e9edef] disabled:bg-gray-100 dark:disabled:bg-[#202c33] placeholder-[#8696a0] dark:placeholder-[#8696a0]" />
                            </div>
                            
                            <button v-if="form.body || form.media" type="submit" :disabled="!isWindowOpen || form.processing" class="p-2 md:p-2.5 rounded-full text-white transition-colors disabled:opacity-50 overflow-hidden bg-[#00a884] hover:bg-[#008f6f]">
                                <svg class="w-5 h-5 ml-0.5 md:ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                            </button>
                            
                            <button v-else @click="toggleVoiceRecording" type="button" :disabled="!isWindowOpen || form.processing" class="p-2 md:p-2.5 rounded-full text-white transition-colors disabled:opacity-50 overflow-hidden bg-[#00a884] hover:bg-[#008f6f]">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path></svg>
                            </button>
                        </form>
                    </div>
                </template>

                <div v-else class="flex-1 flex flex-col items-center justify-center bg-[#f0f2f5] dark:bg-[#202c33] border-l border-gray-200 dark:border-[#313d45]">
                    <div class="text-center w-80">
                        <div class="mx-auto w-32 h-32 bg-white dark:bg-[#2a3942] rounded-full flex items-center justify-center mb-6 shadow-sm border border-gray-100 dark:border-[#313d45]">
                            <svg class="w-full h-full text-[#00a884] fill-current" viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg">
                                <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.995ZM249.12 57.105L292.81 82.265L249.12 107.425L205.43 82.265L249.12 57.105ZM114.49 184.425L96.13 194.995V85.305L121.49 70.705L139.85 60.135V169.815L114.49 184.425ZM91.76 27.425L135.45 52.585L91.76 77.745L48.07 52.585L91.76 27.425ZM43.67 60.135L62.03 70.705L87.39 85.305V202.545V202.555V202.565C87.39 202.735 87.44 202.895 87.46 203.055C87.49 203.265 87.49 203.485 87.55 203.695V203.705C87.6 203.875 87.69 204.035 87.76 204.195C87.84 204.375 87.89 204.575 87.99 204.745C87.99 204.745 87.99 204.755 88 204.755C88.09 204.905 88.22 205.035 88.33 205.175C88.45 205.335 88.55 205.495 88.69 205.635L88.7 205.645C88.82 205.765 88.98 205.855 89.12 205.965C89.28 206.085 89.42 206.225 89.59 206.325C89.6 206.325 89.6 206.325 89.61 206.335C89.62 206.335 89.62 206.345 89.63 206.345L139.87 234.775V285.065L43.67 229.705V60.135ZM244.75 229.705L148.58 285.075V234.775L219.8 194.115L244.75 179.875V229.705ZM297.2 139.625L253.49 164.795V114.995L278.85 100.395L297.21 89.825V139.625H297.2Z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-light text-[#41525d] dark:text-[#e9edef] mb-4">CRM de WhatsApp</h2>
                        <p class="text-[14px] text-[#8696a0]">Selecciona un chat para comenzar a enviar mensajes, fotos y documentos de forma segura.</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>