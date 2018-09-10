import React, {Component} from 'react';

class ToolbarComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            merge: {
                active: false,
                disabled: false,
                classList: [],
            }
        };
    }

    render() {
        return (
            <div id="toolbar">
                <div className="record-count">
                    <ul>
                        <li>
                            <i className="icon close"></i>
                        </li>
                        <li>
                            <p>
                                <span className="label"> 2 </span>
                                record selected
                            </p>
                        </li>
                    </ul>
                </div>
                <div className="segment-actions">
                    <ul>
                        <li>
                            <i
                                className={"icon object ungroup outline"}
                            >
                            </i>
                        </li>
                        <li>
                            <i
                                className={"icon random" +
                                this.state.merge.classList.join(' ') +
                                (this.state.merge.disabled ? ' disabled' : '')}
                                onClick={this.onMergeClick}>
                            </i>
                        </li>
                        <li>
                            <i
                                className={"icon pin"}
                            >
                            </i>
                        </li>
                        <li>
                            <i
                                className={"icon eye"}
                            >
                            </i>
                        </li>
                    </ul>
                </div>
                <div className="cmd-shortcut">
                    <p><span>Shift+click</span> to select , <span>Esc</span> to deselect all </p>
                </div>
            </div>
        );
    }

    onMergeClick = () => {
        if (this.state.merge.active) {
            this.setState({
                merge: {
                    active: !this.state.merge.active,
                    disabled: this.state.merge.disabled,
                    classList: [''],
                }
            });
        } else {
            this.setState({
                merge: {
                    active: !this.state.merge.active,
                    disabled: this.state.merge.disabled,
                    classList: [' active'],
                }
            });
        }


    }
}

export default ToolbarComponent;