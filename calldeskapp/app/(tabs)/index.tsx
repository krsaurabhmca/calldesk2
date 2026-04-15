import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, StyleSheet, RefreshControl, TouchableOpacity, Dimensions, ActivityIndicator } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { apiCall } from '../../services/api';
import { getUser } from '../../services/auth';
import { Users, CalendarClock, CheckCircle2, TrendingUp, PhoneCall, ArrowUpRight, Clock, Target, ChevronRight, BarChart3, ShieldCheck, Zap, PlusCircle, UserPlus, RefreshCcw, LayoutGrid, Award, Briefcase } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

const { width } = Dimensions.get('window');

export default function Dashboard() {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<any>(null);
    const [user, setUser] = useState<any>(null);
    const [refreshing, setRefreshing] = useState(false);
    const router = useRouter();

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        const userData = await getUser();
        setUser(userData);

        const res = await apiCall('dashboard.php');
        if (res.success) {
            setData(res.data);
        }
        setLoading(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchData();
        }, [])
    );

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchData();
        setRefreshing(false);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Converted': return '#10b981';
            case 'Interested': return '#6366f1';
            case 'Lost': return '#ef4444';
            case 'Follow-up': return '#f59e0b';
            default: return '#94a3b8';
        }
    };

    if (loading && !refreshing) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    // Role detection: prioritize API data, fallback to SecureStore user role
    const effectiveRole = data?.role || user?.role;
    const isAdmin = effectiveRole?.toLowerCase() === 'admin';
    const stats = data?.stats;

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.greeting}>Welcome back,</Text>
                    <Text style={styles.userName}>{user?.name || 'User'}</Text>
                </View>
                <View style={[styles.roleBadge, { backgroundColor: isAdmin ? '#eef2ff' : '#f0fdf4' }]}>
                    {isAdmin ? <ShieldCheck size={14} color="#6366f1" /> : <Zap size={14} color="#10b981" />}
                    <Text style={[styles.roleText, { color: isAdmin ? '#6366f1' : '#10b981' }]}>
                        {isAdmin ? 'Admin' : 'Executive'}
                    </Text>
                </View>
                <TouchableOpacity style={styles.addShortcut} onPress={() => router.push('/(tabs)/leads')}>
                    <PlusCircle size={32} color="#6366f1" />
                </TouchableOpacity>
            </View>

            <ScrollView
                showsVerticalScrollIndicator={false}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                {isAdmin ? (
                    /* ADMIN VIEW */
                    <View style={styles.content}>
                        {/* Summary Row */}
                        <View style={styles.summaryRow}>
                            <View style={styles.summaryItem}>
                                <Text style={styles.summaryVal}>{stats?.total_leads || 0}</Text>
                                <Text style={styles.summaryLabel}>Total Leads</Text>
                            </View>
                            <View style={styles.summaryDivider} />
                            <View style={styles.summaryItem}>
                                <Text style={styles.summaryVal}>{stats?.today_calls || 0}</Text>
                                <Text style={styles.summaryLabel}>Active Calls</Text>
                            </View>
                            <View style={styles.summaryDivider} />
                            <View style={styles.summaryItem}>
                                <Text style={styles.summaryVal}>{stats?.today_leads || 0}</Text>
                                <Text style={styles.summaryLabel}>New Today</Text>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Main Hub</Text>
                            <View style={styles.hubGrid}>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#6366f1' }]} onPress={() => router.push('/leads')}>
                                    <Users size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Leads</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#10b981' }]} onPress={() => router.push('/reports')}>
                                    <BarChart3 size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Reports</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#0ea5e9' }]} onPress={() => router.push('/users')}>
                                    <Users size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Team</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#f59e0b' }]} onPress={() => router.push('/projects')}>
                                    <Briefcase size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Projects</Text>
                                </TouchableOpacity>
                            </View>
                        </View>

                        {/* Executive Performance Today */}
                        <View style={styles.section}>
                            <View style={styles.sectionHeader}>
                                <Text style={styles.sectionTitle}>Team Activity Today</Text>
                                <TouchableOpacity onPress={() => router.push('/users')}>
                                    <Text style={styles.seeAll}>View All</Text>
                                </TouchableOpacity>
                            </View>
                            {data?.executive_performance?.length > 0 ? (
                                data.executive_performance.map((exec: any) => (
                                    <View key={exec.id} style={styles.execStatCard}>
                                        <View style={styles.execStatHeader}>
                                            <View style={styles.execAvatarSmall}>
                                                <Text style={styles.execAvatarTextSmall}>{(exec.name || 'U').charAt(0).toUpperCase()}</Text>
                                            </View>
                                            <Text style={styles.execNameText}>{exec.name}</Text>
                                            <View style={[styles.taskBadge, { backgroundColor: exec.pending_tasks > 0 ? '#fee2e2' : '#f0fdf4' }]}>
                                                <Clock size={10} color={exec.pending_tasks > 0 ? '#ef4444' : '#10b981'} />
                                                <Text style={[styles.taskBadgeText, { color: exec.pending_tasks > 0 ? '#ef4444' : '#10b981' }]}>
                                                    {exec.pending_tasks} Tasks
                                                </Text>
                                            </View>
                                        </View>
                                        <View style={styles.execStatGrid}>
                                            <View style={styles.execGridItem}>
                                                <Text style={styles.gridVal}>{exec.total_calls}</Text>
                                                <Text style={styles.gridLabel}>Total</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#ef4444' }]}>{exec.missed_calls}</Text>
                                                <Text style={styles.gridLabel}>Missed</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#10b981' }]}>{exec.incoming_calls}</Text>
                                                <Text style={styles.gridLabel}>In</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#6366f1' }]}>{exec.outgoing_calls}</Text>
                                                <Text style={styles.gridLabel}>Out</Text>
                                            </View>
                                        </View>
                                    </View>
                                ))
                            ) : (
                                <Text style={styles.empty}>No team activity recorded today.</Text>
                            )}
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Quick Actions</Text>
                            <View style={styles.quickActionsGrid}>
                                <TouchableOpacity 
                                    style={styles.actionCard}
                                    onPress={() => router.push({ pathname: '/leads', params: { showAdd: 'true' } })}
                                >
                                    <View style={[styles.actionIcon, { backgroundColor: '#f5f3ff' }]}>
                                        <UserPlus size={20} color="#6366f1" />
                                    </View>
                                    <Text style={styles.actionLabel}>Add Lead</Text>
                                </TouchableOpacity>
                                <TouchableOpacity 
                                    style={styles.actionCard}
                                    onPress={() => router.push('/calls')}
                                >
                                    <View style={[styles.actionIcon, { backgroundColor: '#ecfdf5' }]}>
                                        <RefreshCcw size={20} color="#10b981" />
                                    </View>
                                    <Text style={styles.actionLabel}>Sync Calls</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </View>
                ) : (
                    /* EXECUTIVE VIEW */
                    <View style={styles.content}>
                        <View style={styles.greetingSection}>
                            <Text style={styles.greetText}>My Productivity</Text>
                            <View style={styles.progressRow}>
                                <View style={styles.progressBarLarge}>
                                    <View style={[styles.progressFillLarge, { width: `${stats?.performance_percent || 0}%` }]} />
                                </View>
                                <Text style={styles.percText}>{stats?.performance_percent || 0}%</Text>
                            </View>
                        </View>

                        <View style={styles.statGridCompact}>
                            <TouchableOpacity style={styles.compactStat} onPress={() => router.push('/tasks')}>
                                <CalendarClock size={20} color="#6366f1" />
                                <Text style={styles.compactVal}>{stats?.pending_tasks || 0}</Text>
                                <Text style={styles.compactLabel}>Tasks</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={styles.compactStat} onPress={() => router.push('/leads')}>
                                <Users size={20} color="#10b981" />
                                <Text style={styles.compactVal}>{stats?.my_leads || 0}</Text>
                                <Text style={styles.compactLabel}>My Leads</Text>
                            </TouchableOpacity>
                            <View style={styles.compactStat}>
                                <CheckCircle2 size={20} color="#f59e0b" />
                                <Text style={styles.compactVal}>{stats?.my_converted || 0}</Text>
                                <Text style={styles.compactLabel}>Target</Text>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Daily Tools</Text>
                            <View style={styles.hubGrid}>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#6366f1' }]} onPress={() => router.push({ pathname: '/leads', params: { showAdd: 'true' } })}>
                                    <UserPlus size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Add Lead</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#10b981' }]} onPress={() => router.push('/calls')}>
                                    <RefreshCcw size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Sync Calls</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#f43f5e' }]} onPress={() => router.push('/settings/recording')}>
                                    <PhoneCall size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Recordings</Text>
                                </TouchableOpacity>
                                <TouchableOpacity style={[styles.hubCard, { backgroundColor: '#7c3aed' }]} onPress={() => router.push('/messages')}>
                                    <Zap size={24} color="#fff" />
                                    <Text style={styles.hubLabel}>Shortcuts</Text>
                                </TouchableOpacity>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Quick Actions</Text>
                            <View style={styles.quickActionsGrid}>
                                <TouchableOpacity 
                                    style={styles.actionCard}
                                    onPress={() => router.push({ pathname: '/leads', params: { showAdd: 'true' } })}
                                >
                                    <View style={[styles.actionIcon, { backgroundColor: '#f5f3ff' }]}>
                                        <UserPlus size={20} color="#6366f1" />
                                    </View>
                                    <Text style={styles.actionLabel}>Add Lead</Text>
                                </TouchableOpacity>
                                <TouchableOpacity 
                                    style={styles.actionCard}
                                    onPress={() => router.push('/calls')}
                                >
                                    <View style={[styles.actionIcon, { backgroundColor: '#ecfdf5' }]}>
                                        <RefreshCcw size={20} color="#10b981" />
                                    </View>
                                    <Text style={styles.actionLabel}>Sync Calls</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </View>
                )}

                {/* Shared Section: Recent Activity */}
                <View style={[styles.section, { marginBottom: 30 }]}>
                    <View style={styles.sectionHeader}>
                        <Text style={styles.sectionTitle}>{isAdmin ? 'All Leads' : 'My Leads'}</Text>
                        <TouchableOpacity onPress={() => router.push('/leads')}>
                            <Text style={styles.seeAll}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {data?.recent_leads?.length > 0 ? (
                        data.recent_leads.map((item: any) => (
                            <TouchableOpacity key={item.id} style={styles.prospectItem} onPress={() => router.push('/leads')}>
                                <View style={[styles.statusStrip, { backgroundColor: getStatusColor(item.status) }]} />
                                <View style={styles.prospectInfo}>
                                    <Text style={styles.prospectName}>{item.name}</Text>
                                    <Text style={styles.prospectMeta}>
                                        {item.mobile} {isAdmin && item.assigned_to_name ? `• ${item.assigned_to_name}` : ''}
                                    </Text>
                                </View>
                                <View style={[styles.statusBadge, { borderColor: getStatusColor(item.status) }]}>
                                    <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>{item.status}</Text>
                                </View>
                            </TouchableOpacity>
                        ))
                    ) : (
                        <Text style={styles.empty}>No recent leads found</Text>
                    )}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#fff' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 10, paddingBottom: 16, backgroundColor: '#fff' },
    greeting: { fontSize: 13, color: '#94a3b8', fontWeight: '600' },
    userName: { fontSize: 24, fontWeight: '900', color: '#0f172a' },
    roleBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8, gap: 4 },
    roleText: { fontSize: 10, fontWeight: '800', textTransform: 'uppercase' },
    content: { paddingTop: 10 },
    summaryRow: { flexDirection: 'row', paddingHorizontal: 20, marginBottom: 25, alignItems: 'center' },
    summaryItem: { flex: 1, alignItems: 'center' },
    summaryVal: { fontSize: 22, fontWeight: '900', color: '#0f172a' },
    summaryLabel: { fontSize: 10, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase', marginTop: 2 },
    summaryDivider: { width: 1, height: 25, backgroundColor: '#f1f5f9' },
    section: { paddingHorizontal: 20, marginBottom: 30 },
    sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
    sectionTitle: { fontSize: 18, fontWeight: '900', color: '#0f172a' },
    seeAll: { fontSize: 13, fontWeight: '700', color: '#6366f1' },
    hubGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
    hubCard: { width: (width - 52) / 2, padding: 20, borderRadius: 24, alignItems: 'center', gap: 10, elevation: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 10 },
    hubLabel: { color: '#fff', fontSize: 15, fontWeight: '800' },
    execStatCard: { backgroundColor: '#fff', borderRadius: 24, padding: 18, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9' },
    execStatHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 16, gap: 12 },
    execAvatarSmall: { width: 36, height: 36, borderRadius: 12, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center' },
    execAvatarTextSmall: { color: '#6366f1', fontWeight: '800', fontSize: 14 },
    execNameText: { flex: 1, fontSize: 16, fontWeight: '800', color: '#1e293b' },
    taskBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8, gap: 4 },
    taskBadgeText: { fontSize: 11, fontWeight: '700' },
    execStatGrid: { flexDirection: 'row', justifyContent: 'space-between', backgroundColor: '#f8fafc', padding: 16, borderRadius: 16 },
    execGridItem: { alignItems: 'center', flex: 1 },
    gridVal: { fontSize: 16, fontWeight: '900', color: '#0f172a' },
    gridLabel: { fontSize: 10, color: '#94a3b8', fontWeight: '700', marginTop: 2, textTransform: 'uppercase' },
    greetingSection: { paddingHorizontal: 20, marginBottom: 20 },
    greetText: { fontSize: 16, fontWeight: '800', color: '#1e293b', marginBottom: 10 },
    progressRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    progressBarLarge: { flex: 1, height: 12, backgroundColor: '#f1f5f9', borderRadius: 6, overflow: 'hidden' },
    progressFillLarge: { height: '100%', backgroundColor: '#6366f1', borderRadius: 6 },
    percText: { fontSize: 16, fontWeight: '900', color: '#6366f1' },
    statGridCompact: { flexDirection: 'row', paddingHorizontal: 20, gap: 12, marginBottom: 30 },
    compactStat: { flex: 1, backgroundColor: '#f8fafc', padding: 18, borderRadius: 24, alignItems: 'center', gap: 6, borderWidth: 1, borderColor: '#f1f5f9' },
    compactVal: { fontSize: 18, fontWeight: '900', color: '#0f172a' },
    compactLabel: { fontSize: 10, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase' },
    prospectItem: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', padding: 16, borderRadius: 24, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9' },
    statusStrip: { width: 4, height: 30, borderRadius: 2, marginRight: 16 },
    prospectInfo: { flex: 1 },
    prospectName: { fontSize: 16, fontWeight: '800', color: '#1e293b' },
    prospectMeta: { fontSize: 12, color: '#94a3b8', marginTop: 2 },
    statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 10, borderWidth: 1.5 },
    statusText: { fontSize: 10, fontWeight: '900', textTransform: 'uppercase' },
    empty: { textAlign: 'center', color: '#94a3b8', marginTop: 20, fontSize: 14 },
    addShortcut: { marginLeft: 12 }
});
