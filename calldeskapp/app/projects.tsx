import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, Modal, TextInput, ActivityIndicator, Alert, RefreshControl, ScrollView } from 'react-native';
import { Plus, Trash2, Search, X, Briefcase, AlertCircle, RefreshCcw, ShieldCheck, Users, ChevronRight, CheckCircle2 } from 'lucide-react-native';
import { apiCall } from '../services/api';
import { getUser } from '../services/auth';
import { useRouter } from 'expo-router';
import { useSnackbar } from '../context/SnackbarContext';

export default function ProjectManagement() {
    const { showSnackbar } = useSnackbar();
    const router = useRouter();
    
    const [activeTab, setActiveTab] = useState<'manage' | 'assign'>('manage');
    const [projects, setProjects] = useState<any[]>([]);
    const [assignments, setAssignments] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    
    // Add Project State
    const [showAddModal, setShowAddModal] = useState(false);
    const [newName, setNewName] = useState('');
    const [saving, setSaving] = useState(false);
    
    // Assignment State
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState<any>(null);
    const [userProjects, setUserProjects] = useState<number[]>([]);
    
    const [userRole, setUserRole] = useState<string | null>(null);

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        const userData = await getUser();
        setUserRole(userData?.role || 'executive');

        if (userData?.role !== 'admin') {
            setLoading(false);
            setRefreshing(false);
            return;
        }

        const [projRes, assignRes] = await Promise.all([
            apiCall('projects.php?action=list'),
            apiCall('projects.php?action=user_assignments')
        ]);

        if (projRes.success) setProjects(projRes.data);
        if (assignRes.success) setAssignments(assignRes.data);
        
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

    const handleAddProject = async () => {
        if (!newName) return;
        setSaving(true);
        const res = await apiCall('projects.php', 'POST', {
            action: 'add',
            name: newName
        });
        if (res.success) {
            showSnackbar('Project added', 'success');
            setNewName('');
            setShowAddModal(false);
            fetchData();
        } else {
            showSnackbar(res.message, 'error');
        }
        setSaving(false);
    };

    const handleDeleteProject = (id: number, name: string) => {
        Alert.alert('Delete Project', `Delete "${name}"? This will affect leads linked to this project.`, [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Delete',
                style: 'destructive',
                onPress: async () => {
                    const res = await apiCall(`projects.php?id=${id}`, 'DELETE');
                    if (res.success) {
                        showSnackbar('Project deleted', 'success');
                        fetchData();
                    } else {
                        showSnackbar(res.message, 'error');
                    }
                }
            }
        ]);
    };

    const openAssignModal = (user: any) => {
        setSelectedUser(user);
        const ids = user.project_ids ? user.project_ids.split(',').map((id: string) => parseInt(id)) : [];
        setUserProjects(ids);
        setShowAssignModal(true);
    };

    const toggleProjectForUser = (projectId: number) => {
        setUserProjects(prev => 
            prev.includes(projectId) 
                ? prev.filter(id => id !== projectId) 
                : [...prev, projectId]
        );
    };

    const handleSaveAssignment = async () => {
        setSaving(true);
        const res = await apiCall('projects.php', 'POST', {
            action: 'assign',
            user_id: selectedUser.id,
            project_ids: userProjects.join(',')
        });
        if (res.success) {
            showSnackbar('Permissions updated', 'success');
            setShowAssignModal(false);
            fetchData();
        } else {
            showSnackbar(res.message, 'error');
        }
        setSaving(false);
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
                <Text style={styles.errorSub}>Only administrators can manage project categories.</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={() => router.back()}>
                    <Text style={styles.retryText}>Go Back</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.title}>Project Categories</Text>
                    <Text style={styles.subtitle}>{activeTab === 'manage' ? 'Manage global list' : 'Assign to team members'}</Text>
                </View>
                {activeTab === 'manage' && (
                    <TouchableOpacity style={styles.addBtn} onPress={() => setShowAddModal(true)}>
                        <Plus color="#fff" size={20} />
                    </TouchableOpacity>
                )}
            </View>

            <View style={styles.tabs}>
                <TouchableOpacity 
                    style={[styles.tab, activeTab === 'manage' && styles.tabActive]} 
                    onPress={() => setActiveTab('manage')}
                >
                    <Text style={[styles.tabText, activeTab === 'manage' && styles.tabTextActive]}>Manage List</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                    style={[styles.tab, activeTab === 'assign' && styles.tabActive]} 
                    onPress={() => setActiveTab('assign')}
                >
                    <Text style={[styles.tabText, activeTab === 'assign' && styles.tabTextActive]}>Assign Permissions</Text>
                </TouchableOpacity>
            </View>

            {activeTab === 'manage' ? (
                <FlatList
                    data={projects}
                    contentContainerStyle={{ padding: 20 }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    renderItem={({ item }) => (
                        <View style={styles.projRow}>
                            <View style={styles.projInfo}>
                                <View style={styles.iconCircle}>
                                    <Briefcase size={18} color="#6366f1" />
                                </View>
                                <Text style={styles.projName}>{item.name}</Text>
                            </View>
                            <TouchableOpacity onPress={() => handleDeleteProject(item.id, item.name)}>
                                <Trash2 size={18} color="#ef4444" />
                            </TouchableOpacity>
                        </View>
                    )}
                    ListEmptyComponent={<Text style={styles.empty}>No projects added yet</Text>}
                />
            ) : (
                <FlatList
                    data={assignments}
                    contentContainerStyle={{ padding: 20 }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    renderItem={({ item }) => (
                        <TouchableOpacity style={styles.userRow} onPress={() => openAssignModal(item)}>
                            <View style={styles.userInfo}>
                                <View style={styles.userAvatar}>
                                    <Text style={styles.avatarTxt}>{item.name.charAt(0)}</Text>
                                </View>
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.userName}>{item.name}</Text>
                                    <Text style={styles.userDeps} numberOfLines={1}>
                                        {item.project_names || 'No projects assigned'}
                                    </Text>
                                </View>
                                <ChevronRight size={18} color="#cbd5e1" />
                            </View>
                        </TouchableOpacity>
                    )}
                    ListEmptyComponent={<Text style={styles.empty}>No team members found</Text>}
                />
            )}

            {/* Add Project Modal */}
            <Modal visible={showAddModal} transparent animationType="slide">
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>New Project Category</Text>
                            <TouchableOpacity onPress={() => setShowAddModal(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        <TextInput
                            style={styles.input}
                            placeholder="Category Name (e.g., Luxury Villas, Commercial)"
                            value={newName}
                            onChangeText={setNewName}
                            autoFocus
                        />
                        <TouchableOpacity style={styles.saveBtn} onPress={handleAddProject} disabled={saving}>
                            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>Create Category</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>

            {/* Assign Modal */}
            <Modal visible={showAssignModal} transparent animationType="slide">
                <View style={[styles.modalOverlay, { justifyContent: 'center' }]}>
                    <View style={[styles.modalContent, { height: '80%', borderRadius: 24, marginHorizontal: 20 }]}>
                        <View style={styles.modalHeader}>
                            <View>
                                <Text style={styles.modalTitle}>Assign Projects</Text>
                                <Text style={styles.modalSubtitle}>Allowed for {selectedUser?.name}</Text>
                            </View>
                            <TouchableOpacity onPress={() => setShowAssignModal(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        
                        <ScrollView style={{ flex: 1, marginVertical: 10 }}>
                            {projects.map(p => (
                                <TouchableOpacity 
                                    key={p.id} 
                                    style={[styles.selectRow, userProjects.includes(p.id) && styles.selectRowActive]}
                                    onPress={() => toggleProjectForUser(p.id)}
                                >
                                    <Text style={[styles.selectText, userProjects.includes(p.id) && styles.selectTextActive]}>{p.name}</Text>
                                    {userProjects.includes(p.id) && <CheckCircle2 size={20} color="#6366f1" />}
                                </TouchableOpacity>
                            ))}
                        </ScrollView>

                        <TouchableOpacity style={styles.saveBtn} onPress={handleSaveAssignment} disabled={saving}>
                            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>Save Permissions</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f8fafc' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 20, paddingBottom: 16, backgroundColor: '#fff' },
    title: { fontSize: 22, fontWeight: '800', color: '#1e293b' },
    subtitle: { fontSize: 13, color: '#64748b', marginTop: 2 },
    addBtn: { width: 44, height: 44, borderRadius: 14, backgroundColor: '#6366f1', justifyContent: 'center', alignItems: 'center', elevation: 2 },
    tabs: { flexDirection: 'row', backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    tab: { flex: 1, paddingVertical: 14, alignItems: 'center', borderBottomWidth: 2, borderBottomColor: 'transparent' },
    tabActive: { borderBottomColor: '#6366f1' },
    tabText: { fontSize: 14, fontWeight: '600', color: '#94a3b8' },
    tabTextActive: { color: '#6366f1' },
    projRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#fff', padding: 16, borderRadius: 20, marginBottom: 10, borderWidth: 1, borderColor: '#f1f5f9' },
    projInfo: { flexDirection: 'row', alignItems: 'center', gap: 14 },
    iconCircle: { width: 36, height: 36, borderRadius: 10, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center' },
    projName: { fontSize: 16, fontWeight: '700', color: '#334155' },
    userRow: { backgroundColor: '#fff', padding: 16, borderRadius: 20, marginBottom: 10, borderWidth: 1, borderColor: '#f1f5f9' },
    userInfo: { flexDirection: 'row', alignItems: 'center', gap: 14 },
    userAvatar: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#e0f2fe', justifyContent: 'center', alignItems: 'center' },
    avatarTxt: { color: '#0ea5e9', fontWeight: '800', fontSize: 18 },
    userName: { fontSize: 16, fontWeight: '800', color: '#1e293b' },
    userDeps: { fontSize: 12, color: '#64748b', marginTop: 2 },
    empty: { textAlign: 'center', color: '#94a3b8', marginTop: 40, fontSize: 14 },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: '#fff', borderTopLeftRadius: 32, borderTopRightRadius: 32, padding: 24, elevation: 20 },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 24 },
    modalTitle: { fontSize: 20, fontWeight: '800', color: '#1e293b' },
    modalSubtitle: { fontSize: 13, color: '#64748b', marginTop: 2 },
    input: { backgroundColor: '#f8fafc', height: 56, borderRadius: 16, paddingHorizontal: 16, fontSize: 16, borderWidth: 1, borderColor: '#e2e8f0', marginBottom: 24 },
    saveBtn: { backgroundColor: '#6366f1', height: 56, borderRadius: 16, justifyContent: 'center', alignItems: 'center', elevation: 4 },
    saveText: { color: '#fff', fontSize: 16, fontWeight: '800' },
    selectRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16, borderRadius: 16, marginBottom: 8, backgroundColor: '#f8fafc', borderWidth: 1, borderColor: '#f1f5f9' },
    selectRowActive: { backgroundColor: '#f5f3ff', borderColor: '#c7d2fe' },
    selectText: { fontSize: 15, fontWeight: '600', color: '#475569' },
    selectTextActive: { color: '#6366f1', fontWeight: '700' },
    errorTitle: { fontSize: 18, fontWeight: '800', color: '#1e293b', marginBottom: 8 },
    errorSub: { fontSize: 14, color: '#64748b', textAlign: 'center', marginBottom: 24 },
    retryBtn: { flexDirection: 'row', backgroundColor: '#6366f1', paddingHorizontal: 24, paddingVertical: 14, borderRadius: 16, alignItems: 'center' },
    retryText: { color: '#fff', fontSize: 16, fontWeight: '800' }
});
