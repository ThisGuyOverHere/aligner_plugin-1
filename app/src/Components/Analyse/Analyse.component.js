import React, {Component} from 'react';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";
import {httpGetAlignmentInfo} from "../../HttpRequests/Alignment.http";
import AlignmentScoreComponent from "./AlignmentScore/AlignmentScore.component";
import SegmentAlignedComponent from "./SegmentAligned/SegmentAligned.component";
import SourceComponent from "./Source/Source.component";
import TargetComponent from "./Target/Trget.component";
import env from "../../Constants/Env.constants";


class AnalyseComponent extends Component {
    static propTypes = {};
    pullingId = null;

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: props.match.params.password,
                id: props.match.params.jobID
            },
            progress: 30,
            sourceLang: '',
            sourceLangFileName: '',
            totalSourceSegments: '',
            targetLang: '',
            targetLangFileName: '',
            totalTargetSegments: '',
            actualPhase: 0,
        };
    };

    componentDidMount() {
        // get job info
        httpGetAlignmentInfo(this.state.job.id, this.state.job.password)
            .then(
                response => {
                    //console.log(response);
                    const info = response.data;
                    this.setState({
                        sourceLang: info.source_lang,
                        sourceLangFileName: info.target_filename,
                        totalSourceSegments: info.total_source_segments,
                        targetLang: info.target_lang,
                        targetLangFileName: info.target_filename,
                        totalTargetSegments: info.total_target_segments
                    });
                }
            ).catch(
            error => {
                console.log(error);
            }
        );
        //console.log(this.state.job);
        // pulling
        this.pullingId = setInterval(this.pullingInfo, env.pullingCallInterval);
    };

    componentWillUnmount() {
        // use intervalId from the state to clear the interval
        clearInterval(this.pullingId);
    }

    render() {
        return (
            <div className="container analyse">
                {/* <div id="title">
                    <h1>Matecat's intelligence is currently aligning your files, please wait</h1>
                </div>
                <PreAlignStatus props = {this.props}/>
                <Animation/> */}
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

                <PreAlignStatus jobId={this.state.job.id}
                                jobPassword={this.props.match.params.password}
                                actualPhase = {this.state.actualPhase}/>

                <div className="process-info">
                    <SegmentAlignedComponent/>
                    <AlignmentScoreComponent/>
                </div>
                <Animation/>
            </div>
        );
    }

    pullingInfo = () => {
        // call pulling info api
        console.log('hey');
        // update data to pass to the component
        this.setState({
            actualPhase: this.state.actualPhase + 1
        });
        // get job info
        /*httpGetPullingInfo(this.state.job.id, this.state.job.password)
            .then(
                response => {
                    console.log(error);
                    //console.log(response);
                    const progressInfo = response.data;
                   /* this.setState({

                    });
                }
            ).catch(
            error => {
                console.log(error);
            }
        );*/
    }
}

export default AnalyseComponent;
