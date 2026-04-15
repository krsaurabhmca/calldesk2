import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, Modal, TextInput, ActivityIndicator, Alert, RefreshControl } from 'react-native';
import { Plus, Trash2, X, Share2, AlertCircle, RefreshCcw, ShieldCheck, RotateCw } from 'lucide-react-native';
import { apiCall } from '../services/api';
import { getUser } from '../services/auth';
import { useRouter } from 'expo-router';
import { useSnackbar } from '../context/SnackbarContext';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function LeadSourceManagement() {
    const { showSnackbar } = useSnackbar();
    const router = useRouter();
    
    const [sources, setSources] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [userRole, setUserRole] = useState<string | null>(null);

    // Add Source State
    const [showAddModal, setShowAddModal] = useState(false);
    const [newName, setNewName] = useState('');
    const [saving, setSaving] = useState(false);

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        const userData = await getUser();
        setUserRole(userData?.role || 'executive');

        if (userData?.role !== 'admin') {
            setLoading(false);
            setRefreshing(false);
            return;
        }

        const res = await apiCall('sources.php');
        if (res.success) {
            setSources(res.data);
        } else {
            showSnackbar(res.message, 'error');
        }
        
        setLoading(false);
        setRefreshing(false);
    };

    useEffect(() => {
        fetchData();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchData();
    }, []);

    const handleAddSource = async () => {
        if (!newName.trim()) return;
        setSaving(true);
        const res = await apiCall('sources.php', 'POST', {
            action: 'add',
            source_name: newName.trim()
        });
        if (res.success) {
            showSnackbar('Lead source added', 'success');
            setNewName('');
            setShowAddModal(false);
            fetchData();
        } else {
            showSnackbar(res.message, 'error');
        }
        setSaving(false);
    };

    const handleDeleteSource = (id: number, name: string) => {
        Alert.alert('Delete Source', `Are you sure you want to remove "${name}"? This won't delete existing leads but will hide it from the dropdown.`, [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Delete',
                style: 'destructive',
                onPress: async () => {
                    const res = await apiCall('sources.php', 'POST', {
                        action: 'delete',
                        id: id
                    });
                    if (res.success) {
                        showSnackbar('Source deleted', 'success');
                        fetchData();
                    } else {
                        showSnackbar(res.message, 'error');
                    }
                }
            }
        ]);
    };

    const handleToggleStatus = async (id: number, currentStatus: number) => {
        const newStatus = currentStatus === 1 ? 0 : 1;
        const res = await apiCall('sources.php', 'POST', {
            action: 'toggle_status',
            id: id,
            status: newStatus
        });
        if (res.success) {
            showSnackbar('Status updated', 'success');
            fetchData();
        } else {
            showSnackbar(res.message, 'error');
        }
    };

    if (loading && !refreshing) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    if (userRole && userRole !== 'admin') {
        return (
            <View style={styles.center}>
                <ShieldCheck size={64} color="#ef4444" style={{ marginBottom: 20 }} />
                <Text style={styles.errorTitle}>Access Denied</Text>
                <Text style={styles.errorSub}>Only administrators can manage lead sources.</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={() => router.back()}>
                    <Text style={styles.retryText}>Go Back</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.title}>Lead Sources</Text>
                    <Text style={styles.subtitle}>Manage intake channels</Text>
                </View>
                <TouchableOpacity style={styles.addBtn} onPress={() => setShowAddModal(true)}>
                    <Plus color="#fff" size={20} />
                </TouchableOpacity>
            </View>

            <FlatList
                data={sources}
                contentContainerStyle={{ padding: 20 }}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                renderItem={({ item }) => (
                    <View style={[styles.sourceRow, item.status === 0 && { opacity: 0.6, backgroundColor: '#fdf2f2' }]}>
                        <View style={styles.sourceInfo}>
                            <View style={[styles.iconCircle, { backgroundColor: item.status === 1 ? '#f5f3ff' : '#94a3b8' }]}>
                                <Share2 size={18} color="#fff" />
                            </View>
                            <View>
                                <Text style={styles.sourceName}>{item.source_name}</Text>
                                <View style={[styles.statusTag, { backgroundColor: item.status === 1 ? '#dcfce7' : '#fee2e2' }]}>
                                    <Text style={[styles.statusTagText, { color: item.status === 1 ? '#15803d' : '#b91c1c' }]}>
                                        {item.status === 1 ? 'Active' : 'Disabled'}
                                    </Text>
                                </View>
                            </View>
                        </View>
                        <View style={{ flexDirection: 'row', gap: 8 }}>
                            <TouchableOpacity 
                                style={[styles.statusBtn, { backgroundColor: item.status === 1 ? '#fef3c7' : '#dcfce7' }]}
                                onPress={() => handleToggleStatus(item.id, item.status)}
                            >
                                <RotateCw size={16} color={item.status === 1 ? '#d97706' : '#15803d'} />
                            </TouchableOpacity>
                            <TouchableOpacity 
                                style={styles.deleteBtn}
                                onPress={() => handleDeleteSource(item.id, item.source_name)}
                            >
                                <Trash2 size={18} color="#ef4444" />
                            </TouchableOpacity>
                        </View>
                    </View>
                )}
                ListEmptyComponent={
                    <View style={styles.emptyContainer}>
                        <AlertCircle size={40} color="#cbd5e1" style={{ marginBottom: 16 }} />
                        <Text style={styles.empty}>No sources added yet</Text>
                    </View>
                }
            />

            {/* Add Source Modal */}
            <Modal visible={showAddModal} transparent animationType="slide">
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>New Lead Source</Text>
                            <TouchableOpacity onPress={() => setShowAddModal(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        <TextInput
                            style={styles.input}
                            placeholder="Source Name (e.g., Facebook Ads)"
                            value={newName}
                            onChangeText={setNewName}
                            autoFocus
                        />
                        
                        <Text style={styles.suggestLabel}>Core Presets:</Text>
                        <View style={styles.suggestions}>
                            {['Facebook', 'Google', 'WhatsApp', 'Referral', 'Website', 'Instagram', 'Direct'].map(s => (
                                <TouchableOpacity 
                                    key={s} 
                                    style={styles.suggestItem}
                                    onPress={() => setNewName(s)}
                                >
                                    <Text style={styles.suggestText}>{s}</Text>
                                </TouchableOpacity>
                            ))}
                        </View>

                        <TouchableOpacity style={styles.saveBtn} onPress={handleAddSource} disabled={saving}>
                            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>Add Source</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f8fafc' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 20, paddingBottom: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    title: { fontSize: 22, fontWeight: '800', color: '#1e293b' },
    subtitle: { fontSize: 13, color: '#64748b', marginTop: 2 },
    addBtn: { width: 44, height: 44, borderRadius: 14, backgroundColor: '#6366f1', justifyContent: 'center', alignItems: 'center', elevation: 2 },
    sourceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#fff', padding: 18, borderRadius: 20, marginBottom: 12, elevation: 1, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 8, borderWidth: 1, borderColor: '#f1f5f9' },
    sourceInfo: { flexDirection: 'row', alignItems: 'center', gap: 16 },
    iconCircle: { width: 40, height: 40, borderRadius: 12, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center' },
    sourceName: { fontSize: 16, fontWeight: '700', color: '#1e293b' },
    statusTag: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6, alignSelf: 'flex-start', marginTop: 2 },
    statusTagText: { fontSize: 9, fontWeight: '800', textTransform: 'uppercase' },
    statusBtn: { padding: 10, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
    deleteBtn: { padding: 10, borderRadius: 12, backgroundColor: '#fff1f2' },
    emptyContainer: { alignItems: 'center', marginTop: 60 },
    empty: { color: '#94a3b8', fontSize: 15, fontWeight: '500' },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.7)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: '#fff', borderTopLeftRadius: 32, borderTopRightRadius: 32, padding: 24, paddingBottom: 40 },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 24 },
    modalTitle: { fontSize: 22, fontWeight: '900', color: '#0f172a' },
    input: { backgroundColor: '#f8fafc', height: 60, borderRadius: 20, paddingHorizontal: 20, fontSize: 16, borderWidth: 1, borderColor: '#e2e8f0', marginBottom: 20 },
    suggestLabel: { fontSize: 12, fontWeight: '700', color: '#94a3b8', marginBottom: 12, textTransform: 'uppercase' },
    suggestions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 30 },
    suggestItem: { backgroundColor: '#f1f5f9', paddingHorizontal: 16, paddingVertical: 8, borderRadius: 10 },
    suggestText: { fontSize: 13, color: '#475569', fontWeight: '600' },
    saveBtn: { backgroundColor: '#6366f1', height: 60, borderRadius: 20, justifyContent: 'center', alignItems: 'center', elevation: 8, shadowColor: '#6366f1', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 12 },
    saveText: { color: '#fff', fontSize: 18, fontWeight: '800' },
    errorTitle: { fontSize: 20, fontWeight: '900', color: '#0f172a', marginBottom: 8 },
    errorSub: { fontSize: 14, color: '#64748b', textAlign: 'center', marginBottom: 30 },
    retryBtn: { flexDirection: 'row', backgroundColor: '#6366f1', paddingHorizontal: 30, paddingVertical: 16, borderRadius: 20, alignItems: 'center' },
    retryText: { color: '#fff', fontSize: 16, fontWeight: '800' }
});
