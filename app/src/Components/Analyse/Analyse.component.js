import React, {Component} from 'react';
import ReactGA from 'react-ga';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";
import {httpGetPullingInfo} from "../../HttpRequests/Alignment.http";
import SourceComponent from "./Source/Source.component";
import TargetComponent from "./Target/Trget.component";
import AnalyseError from "./AnalyseError/AnalyseError.component"
import env from "../../Constants/Env.constants";
import ProjectConstants from "../../Constants/Project.constants";
import ProjectStore from "../../Stores/Project.store";
import CompletedAnimation from "./CompletedAnimation/CompletedAnimation.component";

class AnalyseComponent extends Component {
    static propTypes = {};
    pullingId = null;
    startAlign = null;

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: props.match.params.password,
                id: props.match.params.jobID
            },
            sourceLang: '',
            sourceLangFileName: '',
            totalSourceSegments: '',
            targetLang: '',
            targetLangFileName: '',
            totalTargetSegments: '',
            actualPhase: 0,
            phaseName: '',
            progress: 0,
            prev_job: null,
            completed: false,
            redirect: false,
            analyseError: false
        };
    };

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.STORE_JOB_INFO, this.getInfo);
        // pulling
        this.startAlign = new Date();
        this.pullingId = setInterval(this.pullingInfo, env.pullingCallInterval);

    };

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.STORE_JOB_INFO, this.getInfo);
        clearInterval(this.pullingId);
    }

    render() {
        return (
            !this.state.analyseError ?
                this.state.completed ?
                    <CompletedAnimation
                        jobId={this.props.match.params.jobID}
                        jobPassword={this.props.match.params.password}
                    />
                    :
                    <div className="container analyse">
                        <div className="gap"/>
                        <div className="info-animation">
                            <div className="files-info">
                                <SourceComponent
                                    sourceLang={this.state.sourceLang}
                                    sourceLangFileName={this.state.sourceLangFileName}
                                    totalSourceSegments={this.state.totalSourceSegments}
                                />
                                <TargetComponent
                                    targetLang={this.state.targetLang}
                                    targetLangFileName={this.state.targetLangFileName}
                                    totalTargetSegments={this.state.totalTargetSegments}
                                />
                            </div>

                            {(this.state.prev_job > 0) && <div className="prev-job ">
                                <h4> Sorry, but we're a bit backed up right now. </h4>
                                <h4><span> {this.state.prev_job} jobs are ahead of you</span>, please hold on.</h4>
                            </div>}

                            <PreAlignStatus jobId={this.state.job.id}
                                            jobPassword={this.props.match.params.password}
                                            actualPhase={this.state.actualPhase}
                                            progress={this.state.progress}
                                            actualPhaseName={this.state.phaseName}
                                            stopped={this.state.prev_job > 0}
                            />
                            <Animation/>
                        </div>
                    </div>
                :
                <AnalyseError/>
        );
    }

    pullingInfo = () => {
        let data = {};
        // call pulling info api
        httpGetPullingInfo(this.state.job.id, this.state.job.password)
            .then(
                response => {
                    data = response.data;
                    if (this.state.progress === 100) {
                        ReactGA.event({
                            category: 'Timing',
                            action: 'Alignment Completed',
                            label: this.state.job.id,
                            nonInteraction: true,
                            value: parseInt((new Date() - this.startAlign) / 1000)
                        });
                    }
                    // update info
                    this.setState({
                        actualPhase: data.phase,
                        totalSourceSegments: data.source_segments,
                        totalTargetSegments: data.target_segments,
                        phaseName: data.phase_name,
                        progress: +data.progress,
                        prev_job: data.previous_project_number,
                        completed: (this.state.progress === 100)
                    });
                }
            ).catch(
            error => {
                this.setState({
                    analyseError: true,
                });
                clearInterval(this.pullingId);
            }
        );
        //clear pulling interval
        if (this.state.actualPhase === 7) {
            clearInterval(this.pullingId);
        }
    };

    getInfo = (info) => {
        this.setState({
            sourceLang: info.source_lang,
            sourceLangFileName: info.source_filename,
            targetLang: info.target_lang,
            targetLangFileName: info.target_filename,

        });
    }
}

export default AnalyseComponent;
